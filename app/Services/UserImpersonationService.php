<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserImpersonationService
{
    public const ORIGINAL_USER_ID = 'impersonation.original_user_id';
    public const TARGET_USER_ID = 'impersonation.target_user_id';
    public const STARTED_AT = 'impersonation.started_at';
    public const RETURN_URL = 'impersonation.return_url';

    public function active(Request $request): bool
    {
        return $request->session()->has(self::ORIGINAL_USER_ID);
    }

    public function start(Request $request, User $target): void
    {
        $administrator = $request->user();

        if (! $administrator) {
            abort(401);
        }

        if ($this->active($request)) {
            throw ValidationException::withMessages([
                'impersonation' => 'Stop the current impersonation session before starting another one.',
            ]);
        }

        if ($administrator->is($target)) {
            throw ValidationException::withMessages([
                'impersonation' => 'You cannot impersonate your own account.',
            ]);
        }

        if ($target->is_disabled) {
            throw ValidationException::withMessages([
                'impersonation' => 'Disabled accounts cannot be impersonated.',
            ]);
        }

        if ($this->isPrivilegedTarget($target)) {
            throw ValidationException::withMessages([
                'impersonation' => 'Administrative and privileged accounts cannot be impersonated.',
            ]);
        }

        app(AdminActivityService::class)->log(
            action: 'impersonation_started',
            subject: $target,
            newValue: [
                'administrator_id' => $administrator->id,
                'administrator_email' => $administrator->email,
            ],
            description: "Started impersonating {$target->name} ({$target->email}).",
        );

        $request->session()->put([
            self::ORIGINAL_USER_ID => $administrator->id,
            self::TARGET_USER_ID => $target->id,
            self::STARTED_AT => now()->toIso8601String(),
            self::RETURN_URL => route('admin.users.show', $target),
        ]);

        Auth::guard('web')->login($target);
        $request->session()->regenerate();
    }

    public function stop(Request $request): ?string
    {
        $originalUserId = (int) $request->session()->get(self::ORIGINAL_USER_ID, 0);
        $targetUserId = (int) $request->session()->get(self::TARGET_USER_ID, 0);
        $returnUrl = $request->session()->get(self::RETURN_URL);

        if ($originalUserId < 1) {
            return null;
        }

        $administrator = User::query()->find($originalUserId);
        $target = $targetUserId > 0 ? User::query()->find($targetUserId) : null;

        $request->session()->forget([
            self::ORIGINAL_USER_ID,
            self::TARGET_USER_ID,
            self::STARTED_AT,
            self::RETURN_URL,
        ]);

        if (! $administrator || $administrator->is_disabled) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return route('login');
        }

        Auth::guard('web')->login($administrator);
        $request->session()->regenerate();

        app(AdminActivityService::class)->log(
            action: 'impersonation_stopped',
            subject: $target,
            oldValue: $target ? [
                'impersonated_user_id' => $target->id,
                'impersonated_user_email' => $target->email,
            ] : ['impersonated_user_id' => $targetUserId],
            description: $target
                ? "Stopped impersonating {$target->name} ({$target->email})."
                : 'Stopped an impersonation session.',
        );

        return is_string($returnUrl) && str_starts_with($returnUrl, url('/admin/'))
            ? $returnUrl
            : route('admin.users.index');
    }

    public function payload(Request $request): array
    {
        if (! $this->active($request)) {
            return ['active' => false];
        }

        $original = User::query()->find((int) $request->session()->get(self::ORIGINAL_USER_ID));
        $target = $request->user();

        if (! $original || ! $target) {
            return ['active' => false];
        }

        return [
            'active' => true,
            'original_user' => [
                'id' => $original->id,
                'name' => $original->name,
                'email' => $original->email,
            ],
            'target_user' => [
                'id' => $target->id,
                'name' => $target->name,
                'email' => $target->email,
            ],
            'started_at' => $request->session()->get(self::STARTED_AT),
            'stop_url' => route('impersonation.stop'),
        ];
    }

    public function isPrivilegedTarget(User $target): bool
    {
        return collect(['view_admin', 'manage_users', 'impersonate_users'])
            ->contains(fn (string $permission): bool => $target->hasPermission($permission));
    }
}
