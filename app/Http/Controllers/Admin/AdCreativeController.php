<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCreative;
use App\Models\AdvertisingCampaign;
use App\Services\AdCreativeMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdCreativeController extends Controller
{
    public function index(AdvertisingCampaign $adCampaign)
    {
        return Inertia::render('Admin/Advertising/Creatives/Index', [
            'campaign' => $adCampaign->load(['advertiser', 'placements']),
            'creatives' => $adCampaign->creatives()->with(['placements', 'placement', 'approver'])->latest()->get(),
        ]);
    }

    public function create(AdvertisingCampaign $adCampaign) { return $this->form($adCampaign); }

    public function store(Request $request, AdvertisingCampaign $adCampaign, AdCreativeMediaService $media)
    {
        $data = $this->validated($request, $adCampaign);
        $placementIds = $data['placement_ids'];
        unset($data['placement_ids']);
        $data['ad_placement_id'] = $placementIds[0];
        $data['uuid'] = (string) Str::uuid();
        $data['advertising_campaign_id'] = $adCampaign->id;
        $this->attachMedia($request, $data, $media, 'advertising/'.$adCampaign->uuid.'/creatives/'.Str::uuid());
        $creative = $adCampaign->creatives()->create($data);
        $creative->placements()->sync($placementIds);
        return to_route('admin.ad-campaigns.creatives.index', $adCampaign)->with('success', 'Creative added.');
    }

    public function edit(AdvertisingCampaign $adCampaign, AdCreative $creative)
    {
        $this->ensureCampaign($adCampaign, $creative);
        return $this->form($adCampaign, $creative);
    }

    public function update(Request $request, AdvertisingCampaign $adCampaign, AdCreative $creative, AdCreativeMediaService $media)
    {
        $this->ensureCampaign($adCampaign, $creative);
        abort_if($creative->status === 'approved', 422, 'Approved creatives must be returned to draft before editing.');
        $data = $this->validated($request, $adCampaign, $creative);
        $placementIds = $data['placement_ids'];
        unset($data['placement_ids']);
        $data['ad_placement_id'] = $placementIds[0];
        if ($request->hasFile('media') || $request->hasFile('media_original')) {
            $media->deleteCreativeMedia($creative);
            $this->attachMedia($request, $data, $media, 'advertising/'.$adCampaign->uuid.'/creatives/'.$creative->uuid);
        }
        $creative->update($data);
        $creative->placements()->sync($placementIds);
        return to_route('admin.ad-campaigns.creatives.index', $adCampaign)->with('success', 'Creative updated.');
    }

    public function submit(AdvertisingCampaign $adCampaign, AdCreative $creative)
    {
        $this->ensureCampaign($adCampaign, $creative);
        abort_unless(in_array($creative->status, ['draft', 'rejected']), 422);
        abort_unless($creative->media_path, 422, 'Creative media is required.');
        $creative->update(['status' => 'submitted', 'submitted_at' => now(), 'rejection_reason' => null]);
        return back()->with('success', 'Creative submitted for approval.');
    }

    public function decision(Request $request, AdvertisingCampaign $adCampaign, AdCreative $creative)
    {
        $this->ensureCampaign($adCampaign, $creative);
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'rejection_reason' => 'required_if:decision,reject|nullable|string|max:2000']);
        abort_unless($creative->status === 'submitted', 422);
        $creative->update($data['decision'] === 'approve'
            ? ['status' => 'approved', 'approved_at' => now(), 'approved_by' => $request->user()->id, 'rejection_reason' => null]
            : ['status' => 'rejected', 'approved_at' => null, 'approved_by' => $request->user()->id, 'rejection_reason' => $data['rejection_reason']]);
        return back()->with('success', 'Creative decision recorded.');
    }


    public function returnToDraft(AdvertisingCampaign $adCampaign, AdCreative $creative)
    {
        $this->ensureCampaign($adCampaign, $creative);
        abort_unless($creative->status === 'approved', 422, 'Only approved creatives can be returned to draft.');

        $creative->update([
            'status' => 'draft',
            'approved_at' => null,
            'approved_by' => null,
            'submitted_at' => null,
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Creative returned to draft and removed from public ad rotation.');
    }

    public function destroy(AdvertisingCampaign $adCampaign, AdCreative $creative, AdCreativeMediaService $media)
    {
        $this->ensureCampaign($adCampaign, $creative);
        abort_if($creative->status === 'approved', 422, 'Approved creatives cannot be deleted.');
        $media->deleteCreativeMedia($creative);
        $creative->delete();
        return back()->with('success', 'Creative deleted.');
    }

    private function form(AdvertisingCampaign $campaign, ?AdCreative $creative = null)
    {
        return Inertia::render('Admin/Advertising/Creatives/Form', [
            'campaign' => $campaign->load(['advertiser', 'placements']),
            'creative' => $creative?->load(['placements', 'placement']),
        ]);
    }

    private function validated(Request $request, AdvertisingCampaign $campaign, ?AdCreative $creative = null): array
    {
        $imageRequired = ! $creative && $request->input('creative_type', 'image') === 'image';
        $videoRequired = ! $creative && $request->input('creative_type') === 'video';
        $data = $request->validate([
            'placement_ids' => ['required', 'array', 'min:1'],
            'placement_ids.*' => ['integer', 'distinct', Rule::exists('ad_placements', 'id')->where(fn ($q) => $q->whereIn('id', $campaign->placements()->pluck('ad_placements.id')))],
            'name' => 'required|string|max:255', 'creative_type' => 'required|in:image,video',
            'media_original' => [$imageRequired ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:20480'],
            'media' => [$imageRequired || $videoRequired ? 'required' : 'nullable', 'file', Rule::when($request->input('creative_type') === 'image', ['image', 'mimes:jpeg,jpg,png,webp', 'max:20480'], ['mimes:mp4,webm', 'max:102400'])],
            'media_edit_data' => 'nullable|json', 'width' => 'nullable|integer|min:1|max:10000', 'height' => 'nullable|integer|min:1|max:10000',
            'headline' => 'nullable|string|max:255', 'body' => 'nullable|string|max:2000', 'cta_label' => 'nullable|string|max:100',
            'destination_url' => 'required|url|max:1000', 'alt_text' => 'nullable|string|max:1000',
        ]);

        $this->ensurePlacementCompatibility($campaign, $data);

        return $data;
    }

    private function ensurePlacementCompatibility(AdvertisingCampaign $campaign, array $data): void
    {
        if (empty($data['width']) || empty($data['height'])) {
            return;
        }

        $placements = $campaign->placements()->whereIn('ad_placements.id', $data['placement_ids'])->get();
        $incompatible = $placements->filter(fn ($placement) =>
            $placement->width && $placement->height &&
            ((int) $placement->width !== (int) $data['width'] || (int) $placement->height !== (int) $data['height'])
        );

        if ($incompatible->isNotEmpty()) {
            throw ValidationException::withMessages([
                'placement_ids' => 'This creative does not match: '.$incompatible->pluck('name')->join(', ').'. Select placements with matching dimensions.',
            ]);
        }
    }

    private function attachMedia(Request $request, array &$data, AdCreativeMediaService $media, string $directory): void
    {
        if ($data['creative_type'] === 'image') {
            $paths = $media->storeImage($request->file('media_original'), $request->file('media'), $directory);
            $data = array_merge($data, $paths, ['mime_type' => $request->file('media')->getMimeType(), 'file_size' => $request->file('media')->getSize(), 'original_filename' => $request->file('media_original')->getClientOriginalName(), 'media_edit_data' => json_decode($data['media_edit_data'] ?? 'null', true)]);
        } else {
            $paths = $media->storeVideo($request->file('media'), $directory);
            $data = array_merge($data, $paths, ['mime_type' => $request->file('media')->getMimeType(), 'file_size' => $request->file('media')->getSize(), 'original_filename' => $request->file('media')->getClientOriginalName(), 'media_edit_data' => null]);
        }
    }

    private function ensureCampaign(AdvertisingCampaign $campaign, AdCreative $creative): void { abort_unless($creative->advertising_campaign_id === $campaign->id, 404); }
}
