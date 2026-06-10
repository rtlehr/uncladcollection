<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        $allowedSorts = [
            'name',
            'username',
            'email',
            'created_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with(['roles', 'permissions'])
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy($sort, $direction)
                ->get()
                ->map(function (User $user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'is_disabled' => $user->is_disabled,
                        'roles' => $user->roles->pluck('label')->values(),
                        'direct_permissions_count' => $user->permissions->count(),
                        'all_permissions_count' => count($user->allPermissionNames()),
                        'created_at' => $user->created_at?->format('Y-m-d'),
                    ];
                }),

            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'userRecord' => $user->load(['roles', 'permissions']),

            'roles' => Role::query()
                ->with('permissions')
                ->orderBy('label')
                ->get(),

            'permissions' => Permission::query()
                ->orderBy('group_name')
                ->orderBy('label')
                ->get()
                ->groupBy('group_name'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],

            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],

            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],

            'is_disabled' => ['boolean'],
        ]);

        $user->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'is_disabled' => $request->boolean('is_disabled'),
            ]);

        $user->roles()->sync($validated['roles'] ?? []);
        $user->permissions()->sync($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }
}