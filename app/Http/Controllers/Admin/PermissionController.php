<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::query()
                ->orderBy('group_name')
                ->orderBy('label')
                ->get()
                ->groupBy('group_name'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'label' => ['required', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_system' => ['boolean'],
            'is_locked' => ['boolean'],
        ]);

        Permission::create([
            ...$validated,
            'is_system' => $request->boolean('is_system'),
            'is_locked' => $request->boolean('is_locked'),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission): Response
    {
        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_system' => ['boolean'],
            'is_locked' => ['boolean'],
        ]);

        $permission->update([
            ...$validated,
            'is_system' => $request->boolean('is_system'),
            'is_locked' => $request->boolean('is_locked'),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->is_locked) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Locked permissions cannot be deleted.');
        }

        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}