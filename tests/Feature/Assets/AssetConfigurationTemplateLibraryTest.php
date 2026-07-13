<?php

use App\Enums\AssetConfigurationDisplayType;
use App\Models\AssetConfigurationTemplate;
use App\Services\AssetConfigurationTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(config('database.default'))->toBe('mysql');
    expect(config('database.connections.mysql.database'))->toBe('uncladcollection_testing');
});

it('copies a library template into an independent asset configuration payload', function (): void {
    $template = AssetConfigurationTemplate::create([
        'name' => 'Sizes', 'code' => 'sizes', 'display_type' => AssetConfigurationDisplayType::Select,
        'is_required_default' => true, 'is_active' => true,
    ]);
    $template->values()->createMany([
        ['label' => 'Small', 'value' => 'small', 'sort_order' => 10],
        ['label' => 'Large', 'value' => 'large', 'price_adjustment_cents' => 500, 'sort_order' => 20],
    ]);

    $payload = app(AssetConfigurationTemplateService::class)->toAssetGroup($template);

    expect($payload['name'])->toBe('Sizes')
        ->and($payload['is_required'])->toBeTrue()
        ->and($payload['values'])->toHaveCount(2)
        ->and($payload['values'][1]['price_adjustment_cents'])->toBe(500)
        ->and($payload['id'])->toBeNull();
});

it('keeps asset copies unchanged when the template is edited', function (): void {
    $template = AssetConfigurationTemplate::create([
        'name' => 'Colors', 'code' => 'colors', 'display_type' => AssetConfigurationDisplayType::ColorSwatch,
        'is_active' => true,
    ]);
    $template->values()->create(['label' => 'Black', 'value' => 'black', 'swatch_color' => '#000000']);
    $copy = app(AssetConfigurationTemplateService::class)->toAssetGroup($template);
    $template->update(['name' => 'Updated Colors']);

    expect($copy['name'])->toBe('Colors');
});
