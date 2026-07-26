<?php

use App\Enums\PageHelpAudience;
use App\Models\PageHelp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PageHelp\PageHelpTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function pageHelpPermission(string $name): Permission
{
    return Permission::query()->firstOrCreate(
        ['name' => $name],
        ['label' => str($name)->replace('_', ' ')->title(), 'group_name' => 'Page Help'],
    );
}

it('round trips page help content with role and permission names', function (): void {
    $role = Role::query()->create(['name' => 'editor', 'label' => 'Editor']);
    $permission = pageHelpPermission('manage_assets');
    $help = PageHelp::query()->create([
        'page_key' => 'admin.assets.create',
        'title' => 'Creating assets',
        'summary' => 'Summary',
        'content' => '<p>Rich <strong>content</strong>.</p>',
        'audience' => PageHelpAudience::Custom,
        'is_active' => true,
        'is_published' => true,
        'published_at' => now(),
        'sort_order' => 10,
    ]);
    $help->roles()->attach($role);
    $help->permissions()->attach($permission);

    $transfer = app(PageHelpTransferService::class);
    $json = $transfer->exportJson();
    PageHelp::query()->delete();

    $result = $transfer->importJson($json);

    expect($result['created'])->toBe(1);
    $restored = PageHelp::query()->firstOrFail();
    expect($restored->content)->toBe('<p>Rich <strong>content</strong>.</p>')
        ->and($restored->roles()->pluck('name')->all())->toBe(['editor'])
        ->and($restored->permissions()->pluck('name')->all())->toBe(['manage_assets']);
});

it('exports from the admin page and protects import with page help permissions', function (): void {
    $viewAdmin = pageHelpPermission('view_admin');
    $viewHelp = pageHelpPermission('view_page_help_admin');
    $manageHelp = pageHelpPermission('manage_page_help');
    $user = User::factory()->create();
    $user->permissions()->attach([$viewAdmin->id, $viewHelp->id]);

    $this->actingAs($user)->get('/admin/page-help/export')
        ->assertOk()
        ->assertHeader('content-type', 'application/json; charset=UTF-8');

    $payload = app(PageHelpTransferService::class)->exportJson();
    $this->actingAs($user)->post('/admin/page-help/import', [
        'file' => UploadedFile::fake()->createWithContent('page-help.json', $payload),
        'mode' => 'merge',
    ])->assertForbidden();

    $user->permissions()->attach($manageHelp);
    $this->actingAs($user->fresh())->post('/admin/page-help/import', [
        'file' => UploadedFile::fake()->createWithContent('page-help.json', $payload),
        'mode' => 'merge',
    ])->assertRedirect();
});

it('supports dry run and replace mode', function (): void {
    PageHelp::factory()->create(['page_key' => 'admin.old', 'title' => 'Old', 'audience' => PageHelpAudience::Admin, 'sort_order' => 0]);
    $payload = [
        'format' => PageHelpTransferService::FORMAT,
        'version' => PageHelpTransferService::VERSION,
        'entries' => [[
            'page_key' => 'admin.new',
            'title' => 'New',
            'summary' => null,
            'content' => '<p>New</p>',
            'audience' => 'admin',
            'is_active' => true,
            'is_published' => false,
            'published_at' => null,
            'sort_order' => 0,
            'roles' => [],
            'permissions' => [],
        ]],
    ];

    $transfer = app(PageHelpTransferService::class);
    $dryRun = $transfer->importPayload($payload, 'replace', dryRun: true);
    expect($dryRun['created'])->toBe(1)->and($dryRun['deleted'])->toBe(1)
        ->and(PageHelp::query()->where('page_key', 'admin.old')->exists())->toBeTrue();

    $transfer->importPayload($payload, 'replace');
    expect(PageHelp::query()->where('page_key', 'admin.old')->exists())->toBeFalse()
        ->and(PageHelp::query()->where('page_key', 'admin.new')->exists())->toBeTrue();
});
