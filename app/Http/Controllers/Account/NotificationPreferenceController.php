<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
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

    public function update(Request $request, NotificationCategoryRegistry $registry): RedirectResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array'], 'preferences.*.category' => ['required', 'string'], 'preferences.*.in_app_enabled' => ['required', 'boolean'], 'preferences.*.email_enabled' => ['required', 'boolean'], 'preferences.*.email_frequency' => ['required', 'in:immediate,off']]);
        $allowed = array_keys($registry->all());
        foreach ($data['preferences'] as $item) {
            if (! in_array($item['category'], $allowed, true)) continue;
            $definition = $registry->get($item['category']);
            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'category' => $item['category']],
                ['in_app_enabled' => $item['in_app_enabled'], 'email_enabled' => $definition['transactional'] ? true : $item['email_enabled'], 'email_frequency' => $definition['transactional'] ? 'immediate' : $item['email_frequency']]
            );
        }
        return back()->with('success', 'Notification preferences updated.');
    }
}
