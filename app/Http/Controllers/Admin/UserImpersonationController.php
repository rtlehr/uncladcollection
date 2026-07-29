<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserImpersonationController extends Controller
{
    public function index(Request $request, UserImpersonationService $impersonation): Response
    {
        $administrator = $request->user();
        $search = trim($request->string('search')->toString());
        $direction = strtolower($request->string('direction', 'asc')->toString());

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $users = User::query()
            ->with(['roles:id,name,label', 'permissions:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', $direction)
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString()
            ->through(function (User $user) use ($administrator, $impersonation): array {
                $reason = null;

                if ($administrator?->is($user)) {
                    $reason = 'This is your account.';
                } elseif ($user->is_disabled) {
                    $reason = 'Disabled accounts cannot be impersonated.';
                } elseif ($impersonation->isPrivilegedTarget($user)) {
                    $reason = 'Administrative and privileged accounts cannot be impersonated.';
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_disabled' => (bool) $user->is_disabled,
                    'roles' => $user->roles->pluck('label')->values(),
                    'can_impersonate' => $reason === null,
                    'unavailable_reason' => $reason,
                ];
            });

        return Inertia::render('Admin/Users/Impersonate', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'direction' => $direction,
            ],
            'impersonationActive' => $impersonation->active($request),
        ]);
    }

    public function store(
        Request $request,
        User $user,
        UserImpersonationService $impersonation,
    ): RedirectResponse {
        $impersonation->start($request, $user);

        return redirect()
            ->route('account.index')
            ->with('warning', "You are now viewing the site as {$user->name}.");
    }

    public function destroy(
        Request $request,
        UserImpersonationService $impersonation,
    ): RedirectResponse {
        $returnUrl = $impersonation->stop($request);

        if (! $returnUrl) {
            return redirect()
                ->route('home')
                ->with('info', 'No impersonation session was active.');
        }

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Impersonation ended. Your administrator session has been restored.');
    }
}
