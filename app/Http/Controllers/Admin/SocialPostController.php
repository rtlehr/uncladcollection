<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Services\Social\SocialPostPublisher;
use App\Services\Social\XApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SocialPostController extends Controller
{
    public function index(XApiClient $x): Response
    {
        return Inertia::render('Admin/SocialPosts/Index', [
            'posts' => SocialPost::query()->with('user:id,name')->latest()->paginate(30),
            'xConfigured' => $x->configured(),
        ]);
    }

    public function create(XApiClient $x): Response
    {
        return Inertia::render('Admin/SocialPosts/Create', [
            'xConfigured' => $x->configured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()?->id;
        $data['status'] = ($data['scheduled_at'] ?? null) ? 'scheduled' : 'draft';

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $data['media_path'] = $file->store('social-posts', 'public');
            $data['media_disk'] = 'public';
            $data['media_type'] = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        }

        unset($data['media'], $data['remove_media']);
        $post = SocialPost::create($data);

        return redirect()->route('admin.social-posts.edit', $post)->with('success', $post->status === 'scheduled' ? 'X post scheduled.' : 'Draft X post created.');
    }

    public function edit(SocialPost $socialPost, XApiClient $x): Response
    {
        return Inertia::render('Admin/SocialPosts/Edit', [
            'socialPost' => $socialPost,
            'xConfigured' => $x->configured(),
        ]);
    }

    public function update(Request $request, SocialPost $socialPost): RedirectResponse
    {
        abort_unless($socialPost->isEditable(), 422, 'Published or currently publishing posts cannot be edited.');
        $data = $this->validated($request);

        if ($request->boolean('remove_media')) {
            $this->deleteMedia($socialPost);
            $data['media_path'] = null;
            $data['media_disk'] = null;
            $data['media_type'] = null;
        } elseif ($request->hasFile('media')) {
            $this->deleteMedia($socialPost);
            $file = $request->file('media');
            $data['media_path'] = $file->store('social-posts', 'public');
            $data['media_disk'] = 'public';
            $data['media_type'] = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        }

        $data['status'] = ($data['scheduled_at'] ?? null) ? 'scheduled' : 'draft';
        $data['error_message'] = null;
        unset($data['media'], $data['remove_media']);
        $socialPost->update($data);

        return back()->with('success', $socialPost->status === 'scheduled' ? 'X post scheduled.' : 'Draft saved.');
    }

    public function destroy(SocialPost $socialPost): RedirectResponse
    {
        abort_if($socialPost->status === 'publishing', 422, 'A post cannot be deleted while it is publishing.');
        $this->deleteMedia($socialPost);
        $socialPost->delete();
        return redirect()->route('admin.social-posts.index')->with('success', 'Social post deleted.');
    }

    public function publishNow(SocialPost $socialPost, SocialPostPublisher $publisher): RedirectResponse
    {
        abort_if($socialPost->status === 'published', 422, 'This post has already been published.');
        abort_if($socialPost->status === 'publishing', 422, 'This post is already publishing.');

        try {
            $publisher->publish($socialPost);
            return back()->with('success', 'Post published to X.');
        } catch (Throwable $e) {
            return back()->with('error', 'X publish failed: '.$e->getMessage());
        }
    }

    public function verify(XApiClient $x): RedirectResponse
    {
        try {
            $result = $x->verifyCredentials();
            $username = data_get($result, 'data.username');
            return back()->with('success', $username ? "Connected to X as @{$username}." : 'X credentials verified.');
        } catch (Throwable $e) {
            return back()->with('error', 'X connection failed: '.$e->getMessage());
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'text' => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime', 'max:524288'],
            'remove_media' => ['nullable', 'boolean'],
        ]);
    }

    private function deleteMedia(SocialPost $post): void
    {
        if ($post->media_path) {
            Storage::disk($post->media_disk ?: 'public')->delete($post->media_path);
        }
    }
}
