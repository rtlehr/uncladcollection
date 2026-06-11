<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\AdminActivity;
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

        $oldValues = $user->only([
            'name',
            'username',
            'email',
            'is_disabled',
        ]);

        $oldRoles = $user->roles()->pluck('name')->toArray();
        $oldPermissions = $user->permissions()->pluck('name')->toArray();

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_disabled' => $request->boolean('is_disabled'),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);
        $user->permissions()->sync($validated['permissions'] ?? []);

        $activityService = app(\App\Services\AdminActivityService::class);

        $activityService->logChanges(
            subject: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->only([
                'name',
                'username',
                'email',
                'is_disabled',
            ])
        );

        $newRoles = $user->fresh()->roles()->pluck('name')->toArray();

        if ($oldRoles !== $newRoles) {
            $activityService->log(
                action: 'roles_updated',
                subject: $user,
                fieldName: 'roles',
                oldValue: $oldRoles,
                newValue: $newRoles,
                description: 'Updated user roles.'
            );
        }

        $newPermissions = $user->fresh()->permissions()->pluck('name')->toArray();

        if ($oldPermissions !== $newPermissions) {
            $activityService->log(
                action: 'permissions_updated',
                subject: $user,
                fieldName: 'permissions',
                oldValue: $oldPermissions,
                newValue: $newPermissions,
                description: 'Updated user permissions.'
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function show(User $user): Response
    {
        $user->load(['roles', 'permissions']);

        return Inertia::render('Admin/Users/Show', [
            'userRecord' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_disabled' => $user->is_disabled,
                'roles' => $user->roles
                    ->map(fn (Role $role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'label' => $role->label,
                    ])
                    ->values(),
                'permissions' => $user->permissions
                    ->map(fn (Permission $permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'label' => $permission->label,
                        'group_name' => $permission->group_name,
                    ])
                    ->values(),
                'all_permissions_count' => count($user->allPermissionNames()),
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i'),
            ],

            'activities' => AdminActivity::query()
                ->with('user:id,name,email')
                ->where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (AdminActivity $activity) => [
                    'id' => $activity->id,
                    'admin_name' => $activity->user?->name ?? 'System',
                    'action' => $activity->action,
                    'field_name' => $activity->field_name,
                    'old_value' => $activity->old_value,
                    'new_value' => $activity->new_value,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at?->format('Y-m-d H:i'),
                ]),
        ]);
    }

}