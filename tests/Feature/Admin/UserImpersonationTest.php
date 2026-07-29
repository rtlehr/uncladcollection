<?php

use App\Models\AdminActivity;
use App\Models\Permission;
use App\Models\User;
use App\Services\UserImpersonationService;

function impersonationAdministrator(): User
{
    $administrator = User::factory()->create(['email_verified_at' => now()]);

    $permission = Permission::query()->firstOrCreate(
        ['name' => 'impersonate_users'],
        ['label' => 'Impersonate Users', 'group_name' => 'Administration'],
    );

    $viewAdmin = Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['label' => 'View Admin', 'group_name' => 'Administration'],
    );

    $administrator->permissions()->syncWithoutDetaching([$permission->id, $viewAdmin->id]);

    return $administrator;
}

it('allows an authorized administrator to impersonate a customer and restore the administrator session', function (): void {
    $administrator = impersonationAdministrator();
    $customer = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($administrator)
        ->post(route('admin.users.impersonate', $customer))
        ->assertRedirect(route('account.index'));

    $this->assertAuthenticatedAs($customer);
    expect(session(UserImpersonationService::ORIGINAL_USER_ID))->toBe($administrator->id);

    $this->post(route('impersonation.stop'))
        ->assertRedirect(route('admin.users.show', $customer));

    $this->assertAuthenticatedAs($administrator);
    expect(session()->has(UserImpersonationService::ORIGINAL_USER_ID))->toBeFalse();

    expect(AdminActivity::query()->where('action', 'impersonation_started')->exists())->toBeTrue()
        ->and(AdminActivity::query()->where('action', 'impersonation_stopped')->exists())->toBeTrue();
});

it('blocks privileged targets and sensitive customer actions', function (): void {
    $administrator = impersonationAdministrator();
    $privileged = impersonationAdministrator();

    $this->actingAs($administrator)
        ->post(route('admin.users.impersonate', $privileged))
        ->assertSessionHasErrors('impersonation');

    $customer = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($administrator)
        ->post(route('admin.users.impersonate', $customer));

    $this->patch(route('profile.update'), [
        'name' => 'Changed by administrator',
        'username' => 'changed-by-administrator',
        'email' => 'changed@example.com',
    ])->assertRedirect();

    expect($customer->fresh()->name)->not->toBe('Changed by administrator');
});


it('renders a searchable and name-sortable impersonation directory', function (): void {
    $administrator = impersonationAdministrator();
    User::factory()->create(['name' => 'Zelda Customer', 'email' => 'zelda@example.com']);
    $matching = User::factory()->create(['name' => 'Amy Customer', 'email' => 'amy@example.com']);

    $this->actingAs($administrator)
        ->get(route('admin.users.impersonation.index', [
            'search' => 'Amy',
            'direction' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Impersonate')
            ->where('filters.search', 'Amy')
            ->where('filters.direction', 'desc')
            ->has('users.data', 1)
            ->where('users.data.0.id', $matching->id)
            ->where('users.data.0.can_impersonate', true));
});

it('shows the impersonation directory in the admin command center', function (): void {
    $administrator = impersonationAdministrator();

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adminTools', fn ($groups): bool => collect($groups)
                ->flatMap(fn ($group) => $group['tools'])
                ->contains(fn ($tool): bool => $tool['id'] === 'impersonate-users')));
});
