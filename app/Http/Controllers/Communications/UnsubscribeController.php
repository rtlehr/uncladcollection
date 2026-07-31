<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Communications\CommunicationPreferenceService;
use App\Services\Notifications\NotificationCategoryRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class UnsubscribeController extends Controller
{
    public function show(Request $request, User $user, string $category, NotificationCategoryRegistry $registry): Response
    {
        abort_unless(array_key_exists($category, $registry->all()), 404);
        abort_if($registry->get($category)['transactional'], 403);

        return Inertia::render('Communications/Unsubscribe', [
            'category' => ['key' => $category, ...$registry->get($category)],
            'maskedEmail' => $this->maskEmail($user->email),
            'unsubscribeUrl' => URL::temporarySignedRoute('communications.unsubscribe.store', now()->addMinutes(30), ['user' => $user->id, 'category' => $category]),
            'preferencesUrl' => route('account.notification-preferences.edit'),
        ]);
    }

    public function store(Request $request, User $user, string $category, CommunicationPreferenceService $preferences, NotificationCategoryRegistry $registry): RedirectResponse
    {
        abort_unless(array_key_exists($category, $registry->all()), 404);
        abort_if($registry->get($category)['transactional'], 403);

        $current = $user->notificationPreferences()->where('category', $category)->first();
        $preferences->update($user, $category, $current?->in_app_enabled ?? true, false, 'unsubscribe_link', $request);

        return redirect()->route('communications.unsubscribe.confirmed');
    }

    public function confirmed(): Response
    {
        return Inertia::render('Communications/Unsubscribed');
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($name, 0, 1).str_repeat('•', max(2, mb_strlen($name) - 1)).'@'.$domain;
    }
}
