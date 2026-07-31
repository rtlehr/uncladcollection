<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\Communications\CommunicationPreferenceService;
use App\Services\Notifications\NotificationCategoryRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request, NotificationCategoryRegistry $registry): Response
    {
        $stored = NotificationPreference::query()->where('user_id', $request->user()->id)->get()->keyBy('category');
        $categories = collect($registry->all())->map(function (array $definition, string $key) use ($stored): array {
            $preference = $stored->get($key);
            return ['key' => $key, ...$definition, 'in_app_enabled' => $preference?->in_app_enabled ?? true, 'email_enabled' => $preference?->email_enabled ?? $definition['default_email'], 'email_frequency' => $preference?->email_frequency ?? 'immediate'];
        })->values();

        return Inertia::render('Account/Notifications/Preferences', ['categories' => $categories]);
    }

    public function update(Request $request, NotificationCategoryRegistry $registry, CommunicationPreferenceService $preferences): RedirectResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array'], 'preferences.*.category' => ['required', 'string'], 'preferences.*.in_app_enabled' => ['required', 'boolean'], 'preferences.*.email_enabled' => ['required', 'boolean'], 'preferences.*.email_frequency' => ['required', 'in:immediate,off']]);
        $allowed = array_keys($registry->all());
        foreach ($data['preferences'] as $item) {
            if (! in_array($item['category'], $allowed, true)) continue;
            $preferences->update(
                $request->user(),
                $item['category'],
                (bool) $item['in_app_enabled'],
                (bool) $item['email_enabled'],
                'account',
                $request,
            );
        }
        return back()->with('success', 'Notification preferences updated.');
    }
}
