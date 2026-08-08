<?php

namespace App\Http\Controllers\Admin;

use App\Advertising\AdvertiserWorkflowService;
use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Models\AdvertiserMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdvertiserController extends Controller
{
    public function index(AdvertiserWorkflowService $workflow)
    {
        $advertisers = Advertiser::query()
            ->withCount(['campaigns', 'memberships'])
            ->orderBy('name')
            ->get()
            ->map(function (Advertiser $advertiser) use ($workflow) {
                $advertiser->setAttribute('workflow', $workflow->compactSummary($advertiser));
                return $advertiser;
            });

        return Inertia::render('Admin/Advertising/Advertisers/Index', [
            'advertisers' => $advertisers,
        ]);
    }

    public function show(Advertiser $advertiser, AdvertiserWorkflowService $workflow)
    {
        $advertiser->load(['memberships.user', 'campaigns', 'sponsorshipProposals']);

        return Inertia::render('Admin/Advertising/Advertisers/Show', [
            'advertiser' => $advertiser,
            'workflow' => $workflow->summarize($advertiser),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Advertising/Advertisers/Form', [
            'advertiser' => null,
            'users' => [],
            'membershipRoles' => AdvertiserMembership::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['uuid'] = (string) Str::uuid();
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $advertiser = Advertiser::create($data);

        return to_route('admin.advertisers.edit', $advertiser)
            ->with('success', 'Advertiser created. Add portal members below.');
    }

    public function edit(Advertiser $advertiser)
    {
        return Inertia::render('Admin/Advertising/Advertisers/Form', [
            'advertiser' => $advertiser->load('memberships.user'),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'membershipRoles' => AdvertiserMembership::ROLES,
        ]);
    }

    public function update(Request $request, Advertiser $advertiser)
    {
        $advertiser->update($this->data($request));

        return to_route('admin.advertisers.show', $advertiser)
            ->with('success', 'Advertiser updated.');
    }

    public function destroy(Advertiser $advertiser)
    {
        abort_if($advertiser->campaigns()->exists(), 422, 'Advertisers with campaigns cannot be deleted.');
        $advertiser->delete();

        return back()->with('success', 'Advertiser deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,prospect',
            'website_url' => 'nullable|url|max:500',
            'billing_email' => 'nullable|email',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);
    }
}
