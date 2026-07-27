<?php

use App\Enums\AssetConfigurationDisplayType;
use App\Models\AssetConfigurationTemplate;
use Database\Seeders\AssetConfigurationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('installs the default configuration template library', function (): void {
    $this->seed(AssetConfigurationTemplateSeeder::class);

    expect(AssetConfigurationTemplate::query()->count())->toBe(4);

    $expectedTemplates = [
        'apparel_size' => [
            'name' => 'Apparel Size',
            'display_type' => AssetConfigurationDisplayType::Select,
            'values' => 5,
        ],
        'standard_colors' => [
            'name' => 'Standard Colors',
            'display_type' => AssetConfigurationDisplayType::ColorSwatch,
            'values' => 4,
        ],
        'print_size' => [
            'name' => 'Print Size',
            'display_type' => AssetConfigurationDisplayType::Radio,
            'values' => 4,
        ],
        'video_resolution' => [
            'name' => 'Video Resolution',
            'display_type' => AssetConfigurationDisplayType::Radio,
            'values' => 3,
        ],
    ];

    foreach ($expectedTemplates as $code => $expected) {
        $template = AssetConfigurationTemplate::query()
            ->where('code', $code)
            ->firstOrFail();

        expect($template->name)->toBe($expected['name'])
            ->and($template->display_type)->toBe($expected['display_type'])
            ->and($template->is_active)->toBeTrue()
            ->and($template->values()->count())->toBe($expected['values']);
    }
});

it('can be rerun without creating duplicates or removing custom templates', function (): void {
    AssetConfigurationTemplate::query()->create([
        'name' => 'Custom Finish',
        'code' => 'custom_finish',
        'display_type' => AssetConfigurationDisplayType::Select,
        'is_active' => true,
    ]);

    $this->seed(AssetConfigurationTemplateSeeder::class);
    $this->seed(AssetConfigurationTemplateSeeder::class);

    expect(AssetConfigurationTemplate::query()->count())->toBe(5)
        ->and(AssetConfigurationTemplate::query()->where('code', 'custom_finish')->exists())
        ->toBeTrue()
        ->and(AssetConfigurationTemplate::query()->where('code', 'apparel_size')->count())
        ->toBe(1)
        ->and(AssetConfigurationTemplate::query()
            ->where('code', 'apparel_size')
            ->firstOrFail()
            ->values()
            ->where('value', 'small')
            ->count())
        ->toBe(1);
});
