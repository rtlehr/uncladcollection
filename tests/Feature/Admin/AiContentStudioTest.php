<?php

use App\Models\AiContentPolicy;
use App\Models\AiPromptExample;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\ContentStudio\PromptExampleImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function aiContentAdmin(): User
{
    $viewAdmin = Permission::query()->create([
        'name' => 'view_admin',
        'label' => 'View Admin Area',
        'group_name' => 'Administration',
        'description' => 'Access the admin section.',
        'is_system' => true,
        'is_locked' => true,
    ]);

    $manageAiContent = Permission::query()->create([
        'name' => 'manage_ai_content',
        'label' => 'Manage AI Content Studio',
        'group_name' => 'AI Content Studio',
        'description' => 'Generate image prompts and manage AI content.',
        'is_system' => true,
        'is_locked' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'ai_content_test_admin',
        'label' => 'AI Content Test Administrator',
        'description' => 'Test role for AI Content Studio access.',
        'is_system' => false,
        'is_locked' => false,
    ]);

    $role->permissions()->attach([$viewAdmin->id, $manageAiContent->id]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach($role->id);

    return $user;
}

it('imports structured prompt examples and skips duplicates', function () {
    $path=tempnam(sys_get_temp_dir(),'prompts');
    file_put_contents($path,json_encode(['prompts'=>[
        ['title'=>'Family Beach','content'=>'A family enjoying an ordinary beach day.','content_context'=>'family_naturism'],
        ['title'=>'Duplicate','content'=>'A family enjoying an ordinary beach day.','content_context'=>'family_naturism'],
    ]]));
    $stats=app(PromptExampleImporter::class)->import($path);
    expect($stats)->toMatchArray(['created'=>1,'duplicates'=>1,'invalid'=>0]);
    expect(AiPromptExample::count())->toBe(1);
});

it('allows authorized administrators to open the prompt generator', function () {
    $this->actingAs(aiContentAdmin())->get('/admin/ai-content/image-prompts')->assertOk()->assertInertia(fn($page)=>$page->component('Admin/AiContent/SavedPrompts/Index'));
});

it('stores family naturism policy without excluding family contexts', function () {
    $this->seed(\Database\Seeders\AiContentStudioSeeder::class);
    $policy=AiContentPolicy::where('key','family-naturism')->firstOrFail();
    expect($policy->instructions)->toContain('Minors may be present')->toContain('private areas must never be shown');
});
