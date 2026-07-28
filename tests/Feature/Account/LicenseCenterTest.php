<?php

use App\Models\License;
use App\Models\User;
use App\Services\Licenses\LicenseStatusService;

it('describes expired-by-date licenses as expired without rewriting the historical row', function () {
    $license = new License([
        'status' => License::STATUS_ACTIVE,
        'expires_at' => now()->subDay(),
        'downloads_used' => 0,
    ]);

    $status = app(LicenseStatusService::class)->describe($license);

    expect($status['key'])->toBe('expired')
        ->and($status['can_download'])->toBeFalse();
});

it('describes licenses expiring within thirty days as expiring soon', function () {
    $license = new License([
        'status' => License::STATUS_ACTIVE,
        'expires_at' => now()->addDays(10),
        'downloads_used' => 0,
    ]);

    $status = app(LicenseStatusService::class)->describe($license);

    expect($status['key'])->toBe('expiring_soon')
        ->and($status['can_download'])->toBeTrue();
});

it('generates a valid PDF document envelope', function () {
    $pdf = app(\App\Services\Licenses\SimplePdfDocument::class)->render(['Unclad Collection', 'License certificate'], 'Test Certificate');

    expect($pdf)->toStartWith('%PDF-1.4')
        ->and($pdf)->toContain('%%EOF');
});
