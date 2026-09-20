<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\SocialPostAiCampaign;
use App\Models\SocialPostAiSuggestion;
use App\Models\SocialPostPromptAddition;
use App\Services\Ai\ContentStudio\SocialPostIdeaGenerator;
use App\Services\Social\SocialPostPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SocialPostAiController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'campaign' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:pending,accepted,rejected'],
        ]);

        $query = SocialPostAiSuggestion::query()
            ->with('socialPost:id,status,scheduled_at,published_at,x_post_url')
            ->with('sourceSuggestion:id,text,status');

        if (! empty($filters['campaign'])) {
            $query->where('campaign', $filters['campaign']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $savedCampaignModels = SocialPostAiCampaign::query()
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'updated_at']);

        $savedCampaigns = $savedCampaignModels->map(fn (SocialPostAiCampaign $campaign) => [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'subject' => $campaign->subject,
            'updated_at' => $campaign->updated_at?->toIso8601String(),
        ]);

        // Keep campaigns created before campaign profiles were introduced available in the picker.
        // The next generation for one of these campaigns will persist it as a campaign profile.
        $legacyCampaigns = SocialPostAiSuggestion::query()
            ->whereNotNull('campaign')
            ->where('campaign', '!=', '')
            ->latest('id')
            ->get(['campaign', 'subject'])
            ->unique('campaign')
            ->reject(fn (SocialPostAiSuggestion $suggestion) => $savedCampaignModels->contains('name', $suggestion->campaign))
            ->map(fn (SocialPostAiSuggestion $suggestion) => [
                'id' => null,
                'name' => $suggestion->campaign,
                'subject' => $suggestion->subject,
                'updated_at' => null,
            ]);

        $campaigns = $savedCampaigns
            ->concat($legacyCampaigns)
            ->sortBy(fn (array $campaign) => mb_strtolower($campaign['name']))
            ->values();

        return Inertia::render('Admin/SocialPosts/AiGenerator', [
            'suggestions' => $query->latest()->paginate(30)->withQueryString(),
            'campaigns' => $campaigns,
            'promptAdditions' => SocialPostPromptAddition::query()
                ->orderBy('sort_order')
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'prompt_text', 'is_active', 'sort_order', 'updated_at']),
            'filters' => [
                'campaign' => $filters['campaign'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    public function generate(Request $request, SocialPostIdeaGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:5000'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'prompt_addition_ids' => ['nullable', 'array', 'max:30'],
            'prompt_addition_ids.*' => ['integer', 'distinct', 'exists:social_post_prompt_additions,id'],
        ]);

        try {
            $campaign = trim((string) ($data['campaign'] ?? ''));
            $subject = trim($data['subject']);

            if ($campaign !== '') {
                $existingCampaign = SocialPostAiCampaign::query()->where('name', $campaign)->first();

                SocialPostAiCampaign::query()->updateOrCreate(
                    ['name' => $campaign],
                    [
                        'subject' => $subject,
                        'created_by' => $existingCampaign?->created_by ?? $request->user()?->id,
                        'updated_by' => $request->user()?->id,
                    ],
                );
            }

            $promptAdditions = SocialPostPromptAddition::query()
                ->whereIn('id', $data['prompt_addition_ids'] ?? [])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'prompt_text'])
                ->map(fn (SocialPostPromptAddition $addition) => [
                    'id' => $addition->id,
                    'name' => $addition->name,
                    'category' => $addition->category,
                    'prompt_text' => $addition->prompt_text,
                ])
                ->all();

            $result = $generator->generate(
                $subject,
                (int) $data['count'],
                $request->user()?->id,
                $campaign !== '' ? $campaign : null,
                $promptAdditions,
            );

            return back()->with('success', $result['suggestions']->count().' AI post ideas generated. Review them below.');
        } catch (Throwable $e) {
            return back()->with('error', 'AI post generation failed: '.$e->getMessage());
        }
    }

    public function regenerate(Request $request, SocialPostAiSuggestion $suggestion, SocialPostIdeaGenerator $generator): RedirectResponse
    {
        abort_unless($suggestion->status === 'rejected', 422, 'Only rejected suggestions can be regenerated.');

        try {
            $generator->regenerate($suggestion, $request->user()?->id);
            return back()->with('success', 'A fresh replacement suggestion was generated.');
        } catch (Throwable $e) {
            return back()->with('error', 'AI regeneration failed: '.$e->getMessage());
        }
    }

    public function generateMore(Request $request, SocialPostAiSuggestion $suggestion, SocialPostIdeaGenerator $generator): RedirectResponse
    {
        abort_unless($suggestion->status === 'accepted', 422, 'Accept the suggestion before generating more like it.');

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $result = $generator->generateSimilar($suggestion, (int) $data['count'], $request->user()?->id);
            return back()->with('success', $result['suggestions']->count().' new suggestions generated from the accepted post.');
        } catch (Throwable $e) {
            return back()->with('error', 'AI generation failed: '.$e->getMessage());
        }
    }

    public function accept(Request $request, SocialPostAiSuggestion $suggestion): RedirectResponse
    {
        abort_unless($suggestion->status === 'pending', 422, 'This AI suggestion has already been reviewed.');

        $post = SocialPost::create([
            'user_id' => $request->user()?->id,
            'text' => $suggestion->finalText(),
            'status' => 'draft',
        ]);

        $suggestion->update([
            'status' => 'accepted',
            'social_post_id' => $post->id,
            'accepted_at' => now(),
            'rejected_at' => null,
        ]);

        return back()->with('success', 'AI suggestion accepted and saved as an X Post draft.');
    }

    public function reject(SocialPostAiSuggestion $suggestion): RedirectResponse
    {
        abort_unless($suggestion->status === 'pending', 422, 'This AI suggestion has already been reviewed.');

        $suggestion->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'accepted_at' => null,
        ]);

        return back()->with('success', 'AI suggestion rejected.');
    }

    public function publishNow(SocialPostAiSuggestion $suggestion, SocialPostPublisher $publisher): RedirectResponse
    {
        abort_unless($suggestion->status === 'accepted' && $suggestion->socialPost, 422, 'Accept this AI suggestion before publishing it.');
        abort_if($suggestion->socialPost->status === 'published', 422, 'This post has already been published.');

        try {
            $publisher->publish($suggestion->socialPost);
            return back()->with('success', 'Post published to X.');
        } catch (Throwable $e) {
            return back()->with('error', 'X publish failed: '.$e->getMessage());
        }
    }
}
