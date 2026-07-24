<?php

use App\Models\Collection;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function collectionAdmin(): User
{
    $user = User::factory()->create();

    foreach ([
        'view_admin' => 'View administration',
        'manage_collections' => 'Manage collections',
    ] as $name => $label) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            [
                'label' => $label,
                'group_name' => 'Collections',
                'is_system' => true,
                'is_locked' => false,
            ],
        );

        $user->permissions()->syncWithoutDetaching($permission->id);
    }

    return $user->fresh();
}

it('stores an original and edited cover when creating a collection', function () {
    Storage::fake('public');

    $response = $this->actingAs(collectionAdmin())->post('/admin/collections', [
        'name' => 'Coastal Living',
        'description' => 'A beach collection.',
        'sort_order' => 1,
        'is_active' => true,
        'cover_original' => UploadedFile::fake()->image('coast-original.jpg', 1800, 1200),
        'cover_image' => UploadedFile::fake()->image('coast-cover.jpg', 1200, 750),
        'cover_edit_data' => json_encode([
            'preset' => 'collection-cover',
            'zoom' => 1,
            'offsetX' => 0,
            'offsetY' => 0,
            'rotation' => 0,
            'focusX' => .5,
            'focusY' => .5,
            'overlay' => 'thirds',
            'outputWidth' => 1200,
            'outputHeight' => 750,
        ]),
    ]);

    $collection = Collection::query()->firstOrFail();

    $response->assertRedirect(route('admin.collections.edit', $collection));
    expect($collection->cover_original_path)->not->toBeNull()
        ->and($collection->cover_image_path)->not->toBeNull()
        ->and($collection->cover_edit_data['preset'])->toBe('collection-cover');

    Storage::disk('public')->assertExists($collection->cover_original_path);
    Storage::disk('public')->assertExists($collection->cover_image_path);
});

it('uses the dedicated collection cover on the homepage', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->image('cover.jpg', 1200, 750)
        ->store('collections/1/cover/rendered', 'public');

    $collection = Collection::query()->create([
        'name' => 'Featured Collection',
        'slug' => 'featured-collection',
        'description' => 'Featured imagery.',
        'cover_image_path' => $path,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('featuredCollections.0.id', $collection->id)
            ->where('featuredCollections.0.cover_image.thumbnail_url', $collection->cover_image_url));
});

it('removes the collection cover and its stored files', function () {
    Storage::fake('public');

    $original = UploadedFile::fake()
        ->image('original.jpg')
        ->store('collections/1/cover/original', 'public');
    $rendered = UploadedFile::fake()
        ->image('cover.jpg')
        ->store('collections/1/cover/rendered', 'public');

    $collection = Collection::query()->create([
        'name' => 'Removable Cover',
        'slug' => 'removable-cover',
        'cover_original_path' => $original,
        'cover_image_path' => $rendered,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->actingAs(collectionAdmin())->post("/admin/collections/{$collection->id}", [
        '_method' => 'put',
        'name' => $collection->name,
        'description' => '',
        'sort_order' => 0,
        'is_active' => true,
        'remove_cover_image' => true,
    ])->assertRedirect(route('admin.collections.edit', $collection));

    $collection->refresh();

    expect($collection->cover_original_path)->toBeNull()
        ->and($collection->cover_image_path)->toBeNull()
        ->and($collection->cover_edit_data)->toBeNull();

    Storage::disk('public')->assertMissing($original);
    Storage::disk('public')->assertMissing($rendered);
});
