<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseStatusHistory;
use App\Services\AdminActivityService;
use App\Services\Licenses\LicenseStatusService;
use App\Services\Notifications\CustomerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminLicenseController extends Controller
{
    public function __construct(
        private readonly LicenseStatusService $statuses,
        private readonly AdminActivityService $activity,
        private readonly CustomerNotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $licenses = License::query()->with(['user', 'image', 'asset', 'licenseType', 'order'])->withCount('downloads')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('license_key', 'like', "%{$search}%")
                ->orWhere('license_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('image', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('asset', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('order', fn ($query) => $query->where('order_number', 'like', "%{$search}%"))))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString()
            ->through(fn (License $license) => [
                'id' => $license->id, 'license_key' => $license->license_key,
                'status' => $license->status, 'effective_status' => $this->statuses->describe($license), 'license_name' => $license->license_name,
                'downloads_used' => $license->downloads_used, 'download_limit' => $license->download_limit,
                'downloads_count' => $license->downloads_count, 'starts_at' => $license->starts_at?->format('Y-m-d'),
                'expires_at' => $license->expires_at?->format('Y-m-d'), 'created_at' => $license->created_at?->format('Y-m-d H:i'),
                'user' => $license->user ? ['id' => $license->user->id, 'name' => $license->user->name, 'email' => $license->user->email] : null,
                'product' => $license->asset ? ['title' => $license->asset->title, 'slug' => $license->asset->slug, 'kind' => 'asset']
                    : ($license->image ? ['title' => $license->image->title, 'slug' => $license->image->slug, 'kind' => 'image'] : null),
                'order' => $license->order ? ['id' => $license->order->id, 'order_number' => $license->order->order_number] : null,
            ]);

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses, 'filters' => ['search' => $search, 'status' => $status],
            'statuses' => [License::STATUS_ACTIVE, License::STATUS_EXPIRED, License::STATUS_REVOKED, License::STATUS_REFUNDED],
        ]);
    }

    public function show(License $license): Response
    {
        $license->load(['user', 'image', 'asset', 'licenseType', 'order', 'orderItem', 'downloads.assetFile', 'statusHistories.changedBy']);
        return Inertia::render('Admin/Licenses/Show', ['licenseRecord' => [
            'id' => $license->id, 'license_key' => $license->license_key, 'status' => $this->statuses->describe($license),
            'stored_status' => $license->status, 'status_reason' => $license->status_reason,
            'license_name' => $license->license_name, 'license_terms' => $license->license_terms,
            'downloads_used' => $license->downloads_used, 'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'), 'expires_at' => $license->expires_at?->format('Y-m-d'),
            'created_at' => $license->created_at?->format('Y-m-d H:i'),
            'user' => $license->user ? ['id' => $license->user->id, 'name' => $license->user->name, 'email' => $license->user->email] : null,
            'product' => $license->asset ? ['title' => $license->asset->title, 'slug' => $license->asset->slug, 'kind' => 'asset']
                : ($license->image ? ['title' => $license->image->title, 'slug' => $license->image->slug, 'kind' => 'image'] : null),
            'order' => $license->order ? ['id' => $license->order->id, 'order_number' => $license->order->order_number, 'status' => $license->order->status, 'total_formatted' => $license->order->total_formatted, 'paid_at' => $license->order->paid_at?->format('Y-m-d H:i')] : null,
            'downloads' => $license->downloads->sortByDesc('downloaded_at')->map(fn ($download) => ['id' => $download->id, 'download_type' => $download->download_type, 'filename' => $download->original_filename ?: $download->assetFile?->original_filename, 'status' => $download->status, 'ip_address' => $download->ip_address, 'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i')])->values(),
            'history' => $license->statusHistories->map(fn ($history) => ['id' => $history->id, 'from_status' => $history->from_status, 'to_status' => $history->to_status, 'reason' => $history->reason, 'customer_message' => $history->customer_message, 'changed_by' => $history->changedBy?->name, 'created_at' => $history->created_at?->format('Y-m-d H:i')])->values(),
        ]]);
    }

    public function updateLifecycle(Request $request, License $license): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([License::STATUS_ACTIVE, License::STATUS_EXPIRED, License::STATUS_REVOKED, License::STATUS_REFUNDED])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date'],
            'download_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'reset_downloads' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $license, $data): void {
            $old = $license->only(['status', 'status_reason', 'expires_at', 'download_limit', 'downloads_used']);
            $fromStatus = $license->status;
            $license->status = $data['status'];
            $license->status_reason = $data['reason'] ?? null;
            $license->status_changed_at = now();
            $license->expires_at = $data['expires_at'] ?? null;
            $license->download_limit = $data['download_limit'] ?? null;
            if ($data['reset_downloads'] ?? false) $license->downloads_used = 0;
            $license->save();

            LicenseStatusHistory::create([
                'license_id' => $license->id, 'changed_by_user_id' => $request->user()->id,
                'from_status' => $fromStatus, 'to_status' => $license->status,
                'reason' => $data['reason'] ?? null, 'customer_message' => $data['customer_message'] ?? null,
                'metadata' => ['download_limit' => $license->download_limit, 'downloads_reset' => (bool) ($data['reset_downloads'] ?? false)],
            ]);
            $this->activity->logChanges($license, $old, $license->only(array_keys($old)), 'license_updated');
            $license->loadMissing('user');
            DB::afterCommit(function () use ($license, $data): void {
                if (! $license->user) return;
                $label = ucfirst($license->status);
                $message = $data['customer_message'] ?: ($data['reason'] ?: "Your license {$license->license_key} is now {$label}.");
                $this->notifications->send($license->user, 'licenses', "License {$label}", $message, route('account.licenses.show', $license), 'View license', ['license_id' => $license->id, 'status' => $license->status], 'license.status_updated', [
                    'license_key' => $license->license_key,
                    'license_name' => $license->license_name,
                    'license_status' => $label,
                    'license_message' => $message,
                    'license_url' => route('account.licenses.show', $license),
                ]);
            });
        });

        return back()->with('success', 'License lifecycle settings updated.');
    }
}
