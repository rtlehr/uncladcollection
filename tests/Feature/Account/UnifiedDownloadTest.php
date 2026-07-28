<?php

use App\Enums\OrderFulfillmentStatus;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function createAssetDownloadLicense(User $user, int $limit = 5): array
{
    Storage::fake('public');

    $asset = Asset::factory()->published()->create();
    $file = AssetFile::factory()->for($asset)->create();
    Storage::disk('public')->put($file->path, 'licensed file contents');

    $licenseType = LicenseType::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'status' => Order::STATUS_PAID,
        'fulfillment_status' => OrderFulfillmentStatus::Fulfilled->value,
        'subtotal_cents' => 1000,
        'total_cents' => 1000,
        'currency' => 'USD',
        'payment_provider' => Order::PAYMENT_PROVIDER_MANUAL,
        'paid_at' => now(),
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'image_id' => null,
        'asset_id' => $asset->id,
        'license_type_id' => $licenseType->id,
        'status' => OrderItem::STATUS_ACTIVE,
        'fulfillment_type' => 'digital',
        'commerce_version' => '2.0',
        'quantity' => 1,
        'unit_price_cents' => 1000,
        'total_price_cents' => 1000,
        'image_title' => $asset->title,
        'asset_title' => $asset->title,
        'license_name' => $licenseType->name,
        'included_asset_files_snapshot' => [[
            'asset_file_id' => $file->id,
            'uuid' => $file->uuid,
            'original_filename' => $file->original_filename,
            'extension' => $file->extension,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
        ]],
    ]);
    $license = License::create([
        'user_id' => $user->id,
        'image_id' => null,
        'asset_id' => $asset->id,
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'license_type_id' => $licenseType->id,
        'status' => License::STATUS_ACTIVE,
        'fulfillment_type' => 'digital',
        'commerce_version' => '2.0',
        'starts_at' => now(),
        'download_limit' => $limit,
        'downloads_used' => 0,
        'license_name' => $licenseType->name,
        'license_terms' => $licenseType->usage_terms,
        'included_asset_files_snapshot' => $item->included_asset_files_snapshot,
    ]);

    return compact('asset', 'file', 'license');
}

it('downloads a file included in the purchased asset snapshot', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    ['file' => $file, 'license' => $license] = createAssetDownloadLicense($user);

    $this->actingAs($user)
        ->get(route('account.licenses.files.download', [$license, $file]))
        ->assertOk();

    $this->assertDatabaseHas('downloads', [
        'user_id' => $user->id,
        'license_id' => $license->id,
        'asset_file_id' => $file->id,
        'download_type' => 'asset_file',
    ]);

    expect($license->fresh()->downloads_used)->toBe(1);
});

it('rejects a file that was not included in the purchase snapshot', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    ['asset' => $asset, 'license' => $license] = createAssetDownloadLicense($user);
    $other = AssetFile::factory()->for($asset)->create();
    Storage::disk('public')->put($other->path, 'other contents');

    $this->actingAs($user)
        ->get(route('account.licenses.files.download', [$license, $other]))
        ->assertNotFound();
});

it('prevents another customer from downloading the licensed file', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    ['file' => $file, 'license' => $license] = createAssetDownloadLicense($owner);

    $this->actingAs($other)
        ->get(route('account.licenses.files.download', [$license, $file]))
        ->assertForbidden();
});

it('enforces the license download limit', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    ['file' => $file, 'license' => $license] = createAssetDownloadLicense($user, 1);
    $license->update(['downloads_used' => 1]);

    $this->actingAs($user)
        ->get(route('account.licenses.files.download', [$license, $file]))
        ->assertForbidden();
});
