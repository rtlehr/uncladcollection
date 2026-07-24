<?php

namespace Tests\Feature\Admin;

use App\Models\AiKeywordExclusion;
use App\Models\Permission;
use App\Models\User;
use App\Services\Ai\Support\AiKeywordExclusionFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiKeywordExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_keyword_exclusions(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/admin/ai-keyword-exclusions', [
            'keyword' => 'Sensual',
            'notes' => 'Not useful for marketplace search.',
        ])->assertRedirect();

        $item = AiKeywordExclusion::query()->firstOrFail();
        $this->assertSame('sensual', $item->normalized_keyword);
        $this->assertTrue($item->is_active);

        $this->actingAs($user)->patch("/admin/ai-keyword-exclusions/{$item->id}", [
            'is_active' => false,
        ])->assertRedirect();

        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($user)->delete("/admin/ai-keyword-exclusions/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('ai_keyword_exclusions', ['id' => $item->id]);
    }

    public function test_filter_is_exact_case_insensitive_and_ignores_disabled_entries(): void
    {
        AiKeywordExclusion::query()->create(['keyword' => 'Erotic', 'is_active' => true]);
        AiKeywordExclusion::query()->create(['keyword' => 'art', 'is_active' => true]);
        AiKeywordExclusion::query()->create(['keyword' => 'sunset', 'is_active' => false]);

        $result = app(AiKeywordExclusionFilter::class)->filter([
            'EROTIC', 'artistic', 'art', 'sunset', 'Nature', 'nature',
        ]);

        $this->assertSame(['artistic', 'sunset', 'Nature'], $result);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $permissions = collect([
            ['name' => 'view_admin', 'label' => 'View Admin', 'group_name' => 'Administration'],
            ['name' => 'manage_images', 'label' => 'Manage Images and Assets', 'group_name' => 'Assets'],
        ])->map(fn (array $attributes) => Permission::query()->firstOrCreate(
            ['name' => $attributes['name']],
            [...$attributes, 'description' => null, 'is_system' => true, 'is_locked' => false],
        ));
        $user->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
        return $user;
    }
}
