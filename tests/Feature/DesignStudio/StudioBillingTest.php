<?php

use App\Models\DesignExport;
use App\Models\DesignProject;
use App\Models\StudioCreditPackage;
use App\Models\StudioCreditTransaction;
use App\Models\User;
use App\Services\DesignStudio\StudioCreditService;
use Database\Seeders\StudioCreditPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function studioProjectFor(User $user): DesignProject
{
    return DesignProject::create([
        'user_id' => $user->id,
        'license_id' => null,
        'asset_id' => null,
        'title' => 'Studio billing test',
        'canvas_width' => 1080,
        'canvas_height' => 1080,
        'design_json' => ['version' => 2, 'fabric' => ['objects' => []]],
        'status' => 'draft',
    ]);
}

function studioExportFor(DesignProject $project, User $user): DesignExport
{
    return $project->exports()->create([
        'request_token' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'width' => 1080,
        'height' => 1080,
        'format' => 'jpg',
        'fit_mode' => 'contain',
        'status' => 'pending',
        'render_engine' => 'browser-fabric',
    ]);
}

it('reserves a credit and only posts the debit after a successful export', function () {
    $user = User::factory()->create();
    $project = studioProjectFor($user);
    $export = studioExportFor($project, $user);

    StudioCreditTransaction::create([
        'user_id' => $user->id,
        'type' => StudioCreditTransaction::TYPE_PROMOTION,
        'status' => StudioCreditTransaction::STATUS_POSTED,
        'credits' => 2,
        'currency' => 'USD',
        'posted_at' => now(),
    ]);

    $service = app(StudioCreditService::class);
    expect($service->balance($user))->toBe(2)
        ->and($service->availableBalance($user))->toBe(2);

    $reservation = $service->reserveForExport($user, $export);
    expect($reservation->status)->toBe(StudioCreditTransaction::STATUS_PENDING)
        ->and($reservation->credits)->toBe(-1)
        ->and($service->balance($user))->toBe(2)
        ->and($service->availableBalance($user))->toBe(1);

    $service->consumeForExport($export->refresh());
    expect($reservation->refresh()->status)->toBe(StudioCreditTransaction::STATUS_POSTED)
        ->and($service->balance($user))->toBe(1)
        ->and($service->availableBalance($user))->toBe(1);
});

it('releases a reserved credit when an export fails', function () {
    $user = User::factory()->create();
    $project = studioProjectFor($user);
    $export = studioExportFor($project, $user);

    StudioCreditTransaction::create([
        'user_id' => $user->id,
        'type' => StudioCreditTransaction::TYPE_PROMOTION,
        'status' => StudioCreditTransaction::STATUS_POSTED,
        'credits' => 1,
        'currency' => 'USD',
        'posted_at' => now(),
    ]);

    $service = app(StudioCreditService::class);
    $reservation = $service->reserveForExport($user, $export);
    expect($service->availableBalance($user))->toBe(0);

    $service->releaseForExport($export);
    expect($reservation->refresh()->status)->toBe(StudioCreditTransaction::STATUS_VOID)
        ->and($service->balance($user))->toBe(1)
        ->and($service->availableBalance($user))->toBe(1);
});

it('seeds separate Creative Studio export packages', function () {
    $this->seed(StudioCreditPackageSeeder::class);

    expect(StudioCreditPackage::query()->where('slug', 'studio-single-export')->value('price_cents'))->toBe(100)
        ->and(StudioCreditPackage::query()->where('slug', 'studio-10-pack')->value('credits'))->toBe(10)
        ->and(StudioCreditPackage::query()->where('slug', 'studio-50-pack')->value('credits'))->toBe(50);
});
