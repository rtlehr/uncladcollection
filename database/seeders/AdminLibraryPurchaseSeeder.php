<?php

namespace Database\Seeders;

use App\Commerce\Checkout\CheckoutEngine;
use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetFulfillmentType;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AdminLibraryPurchaseSeeder extends Seeder
{
    private const SEED_KEY = 'admin-library-demo-purchase-v2';
    private const DISK = 'asset-files';

    public function run(CheckoutEngine $checkoutEngine): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'AdminLibraryPurchaseSeeder creates simulated Stripe purchase history and is disabled in production.'
            );
        }

        $admin = $this->adminUser();

        if (! $admin) {
            $this->command?->error('No enabled user with the admin role was found.');
            return;
        }

        $licenseType = LicenseType::query()
            ->where('slug', 'commercial-use')
            ->where('is_active', true)
            ->first();

        if (! $licenseType) {
            $this->call(LicenseTypeSeeder::class);
            $licenseType = LicenseType::query()
                ->where('slug', 'commercial-use')
                ->where('is_active', true)
                ->first();
        }

        if (! $licenseType) {
            throw new RuntimeException('The Commercial Use license type could not be found or created.');
        }

        $assets = collect($this->assetDefinitions())
            ->map(fn (array $definition): array => $this->seedDemoAsset($definition, $licenseType))
            ->values();

        $alreadyLicensedAssetIds = License::query()
            ->where('user_id', $admin->id)
            ->where('status', License::STATUS_ACTIVE)
            ->whereIn('asset_id', $assets->pluck('asset.id'))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('asset_id');

        $selections = $assets
            ->reject(fn (array $selection): bool => $alreadyLicensedAssetIds->contains($selection['asset']->id))
            ->values();

        if ($selections->isEmpty()) {
            $this->command?->info("All four Design Studio demo images are already licensed to {$admin->email}.");
            $this->command?->info('They should already appear in My Library and Add from My Library.');
            return;
        }

        $order = DB::transaction(function () use ($admin, $selections): Order {
            $subtotal = (int) $selections->sum(fn (array $selection): int => (int) $selection['offering']->price_cents);

            $order = Order::create([
                'user_id' => $admin->id,
                'status' => Order::STATUS_PENDING,
                'fulfillment_status' => 'new',
                'commerce_version' => CheckoutEngine::COMMERCE_VERSION,
                'subtotal_cents' => $subtotal,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $subtotal,
                'currency' => 'USD',
                'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
                'checkout_locked_at' => now(),
                'metadata' => [
                    'seed_key' => self::SEED_KEY,
                    'seeded_demo_purchase' => true,
                    'stripe_mode' => 'test',
                    'purpose' => 'Populate the administrator My Library for Design Studio testing.',
                ],
                'checkout_snapshot' => [
                    'version' => 1,
                    'captured_at' => now()->toIso8601String(),
                    'seeded_demo_purchase' => true,
                    'items' => $selections->map(fn (array $selection): array => [
                        'kind' => 'asset',
                        'asset_id' => $selection['asset']->id,
                        'asset_offering_id' => $selection['offering']->id,
                        'license_type_id' => $selection['offering']->license_type_id,
                        'quantity' => 1,
                        'line_total_cents' => (int) $selection['offering']->price_cents,
                        'currency' => 'USD',
                    ])->all(),
                ],
            ]);

            foreach ($selections as $selection) {
                $this->createOrderItem($order, $selection['asset'], $selection['offering'], $selection['files']);
            }

            return $order;
        });

        $this->applyStripeSuccessReferences($order);
        $paidOrder = $checkoutEngine->markPaid($order);

        $assetTitles = $paidOrder->licenses()
            ->with('asset:id,title')
            ->get()
            ->pluck('asset.title')
            ->filter()
            ->values();

        $this->command?->newLine();
        $this->command?->info("Created simulated successful Stripe purchase for admin: {$admin->name} <{$admin->email}>");
        $this->command?->info("Order: {$paidOrder->order_number}");
        $this->command?->info("Stripe Checkout Session: {$paidOrder->stripe_checkout_session_id}");
        $this->command?->info("Stripe Payment Intent: {$paidOrder->stripe_payment_intent_id}");
        $this->command?->info('Licensed assets: '.$assetTitles->implode(', '));
        $this->command?->info('These images are now available in My Library and the Design Studio library picker.');
        $this->command?->info('The normal paid-order notification/email flow was triggered by CheckoutEngine::markPaid().');
    }

    private function adminUser(): ?User
    {
        return User::query()
            ->where('is_disabled', false)
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->orderByRaw("CASE WHEN username = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    /** @return array{asset: Asset, offering: AssetOffering, files: Collection<int, AssetFile>} */
    private function seedDemoAsset(array $definition, LicenseType $licenseType): array
    {
        return DB::transaction(function () use ($definition, $licenseType): array {
            $asset = Asset::withTrashed()->firstOrNew(['slug' => $definition['slug']]);
            if ($asset->trashed()) {
                $asset->restore();
            }

            $asset->fill([
                'uuid' => $asset->uuid ?: (string) Str::uuid(),
                'title' => $definition['title'],
                'description' => $definition['description'],
                'asset_type' => AssetType::Image,
                'status' => AssetStatus::Published,
                'photographer' => 'Unclad Collection Studio',
                'sort_order' => $definition['sort_order'],
                'is_active' => true,
                'is_featured' => false,
                'is_ai_generated' => false,
                'allows_quantity' => false,
                'fulfillment_type' => AssetFulfillmentType::Digital,
                'collects_shipping_address' => false,
                'shipping_address_required' => false,
                'published_at' => now()->subDays($definition['days_ago']),
                'metadata' => [
                    'development_seed' => true,
                    'design_studio_demo' => true,
                    'seed_key' => self::SEED_KEY,
                ],
            ]);
            $asset->save();

            $preview = $this->seedFile($asset, $definition, AssetFileRole::Preview, false, 'preview');
            $download = $this->seedFile($asset, $definition, AssetFileRole::HighResolution, true, 'highres');

            $asset->update(['primary_preview_file_id' => $preview->id]);

            $offering = AssetOffering::withTrashed()->firstOrNew([
                'asset_id' => $asset->id,
                'license_type_id' => $licenseType->id,
            ]);
            if ($offering->trashed()) {
                $offering->restore();
            }
            $offering->fill([
                'name' => 'Commercial Image License',
                'description' => 'High-resolution image licensed for Design Studio testing.',
                'image_units' => 1,
                'video_units' => 0,
                'price_cents' => $definition['price_cents'],
                'price_adjustment_cents' => 0,
                'price_override_cents' => null,
                'currency' => 'USD',
                'download_limit' => 10,
                'expires_after_days' => null,
                'include_all_active_files' => false,
                'is_active' => true,
                'sort_order' => 10,
                'metadata' => [
                    'development_seed' => true,
                    'design_studio_demo' => true,
                ],
            ]);
            $offering->save();
            $offering->files()->sync([$download->id => ['sort_order' => 10]]);

            return [
                'asset' => $asset->fresh(),
                'offering' => $offering->fresh(['licenseType']),
                'files' => collect([$download->fresh()]),
            ];
        });
    }

    private function seedFile(Asset $asset, array $definition, AssetFileRole $role, bool $downloadable, string $variant): AssetFile
    {
        $fixturePath = database_path('seeders/assets/'.$definition['fixture']);
        if (! is_file($fixturePath)) {
            throw new RuntimeException("Required demo image fixture is missing: {$fixturePath}");
        }

        $extension = strtolower(pathinfo($definition['fixture'], PATHINFO_EXTENSION));
        $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
        $directory = "assets/development/design-studio/{$asset->slug}/{$variant}";
        $storedFilename = "{$variant}.{$extension}";
        $storagePath = $directory.'/'.$storedFilename;

        Storage::disk(self::DISK)->put($storagePath, file_get_contents($fixturePath));

        $file = AssetFile::withTrashed()->firstOrNew([
            'asset_id' => $asset->id,
            'original_filename' => "{$asset->slug}-{$variant}.{$extension}",
        ]);
        if ($file->trashed()) {
            $file->restore();
        }
        $file->fill([
            'uuid' => $file->uuid ?: (string) Str::uuid(),
            'role' => $role,
            'media_type' => AssetMediaType::Image,
            'disk' => self::DISK,
            'directory' => $directory,
            'stored_filename' => $storedFilename,
            'extension' => $extension,
            'mime_type' => $mime,
            'size_bytes' => filesize($fixturePath),
            'checksum_sha256' => hash_file('sha256', $fixturePath),
            'sort_order' => $downloadable ? 20 : 10,
            'width' => $definition['width'],
            'height' => $definition['height'],
            'metadata' => [
                'development_seed' => true,
                'design_studio_demo' => true,
                'fixture' => $definition['fixture'],
            ],
            'processing_status' => AssetFileProcessingStatus::Ready,
            'virus_scan_status' => AssetFileScanStatus::Clean,
            'is_downloadable' => $downloadable,
            'is_active' => true,
            'is_legacy' => false,
        ]);
        $file->save();

        return $file;
    }

    /** @param Collection<int, AssetFile> $files */
    private function createOrderItem(Order $order, Asset $asset, AssetOffering $offering, Collection $files): OrderItem
    {
        $licenseType = $offering->licenseType;

        return OrderItem::create([
            'order_id' => $order->id,
            'image_id' => null,
            'asset_id' => $asset->id,
            'license_type_id' => $offering->license_type_id,
            'asset_offering_id' => $offering->id,
            'status' => OrderItem::STATUS_PENDING,
            'fulfillment_type' => AssetFulfillmentType::Digital->value,
            'commerce_version' => CheckoutEngine::COMMERCE_VERSION,
            'quantity' => 1,
            'unit_price_cents' => (int) $offering->price_cents,
            'total_price_cents' => (int) $offering->price_cents,
            'image_title' => $asset->title,
            'asset_title' => $asset->title,
            'license_name' => (string) ($licenseType?->name ?? $offering->name),
            'offering_name' => $offering->name,
            'license_terms' => $licenseType?->usage_terms,
            'configuration_hash' => null,
            'configuration_snapshot' => [],
            'shipping_address_snapshot' => null,
            'pricing_snapshot' => [
                'seeded_demo_purchase' => true,
                'base_unit_price_cents' => (int) $offering->price_cents,
                'final_unit_price_cents' => (int) $offering->price_cents,
                'line_total_cents' => (int) $offering->price_cents,
                'currency' => 'USD',
            ],
            'included_asset_files_snapshot' => $files->map(fn (AssetFile $file): array => [
                'asset_file_id' => $file->id,
                'uuid' => $file->uuid,
                'role' => $file->role?->value ?? (string) $file->role,
                'media_type' => $file->media_type?->value ?? (string) $file->media_type,
                'original_filename' => $file->original_filename,
                'extension' => $file->extension,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'checksum_sha256' => $file->checksum_sha256,
            ])->values()->all(),
            'metadata' => [
                'seeded_demo_purchase' => true,
                'seed_key' => self::SEED_KEY,
            ],
        ]);
    }

    private function applyStripeSuccessReferences(Order $order): void
    {
        $token = Str::lower(Str::random(24));

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $order->stripe_checkout_session_id ?: 'cs_test_uc_demo_'.$token,
            'stripe_payment_intent_id' => $order->stripe_payment_intent_id ?: 'pi_uc_demo_'.$token,
            'payment_reference' => $order->payment_reference ?: 'pi_uc_demo_'.$token,
        ]);
    }

    private function assetDefinitions(): array
    {
        return [
            [
                'slug' => 'design-studio-demo-coastal',
                'title' => 'Coastal Morning — Design Studio Demo',
                'description' => 'Demo licensed image for testing the Unclad Collection Design Studio library workflow.',
                'fixture' => 'coastal-morning-highres.jpg',
                'width' => 2400,
                'height' => 1600,
                'price_cents' => 1299,
                'sort_order' => 910,
                'days_ago' => 8,
            ],
            [
                'slug' => 'design-studio-demo-horizon',
                'title' => 'Horizon Study — Design Studio Demo',
                'description' => 'Demo licensed image for testing the Unclad Collection Design Studio library workflow.',
                'fixture' => 'horizon-vector-preview.png',
                'width' => 1200,
                'height' => 800,
                'price_cents' => 1499,
                'sort_order' => 920,
                'days_ago' => 6,
            ],
            [
                'slug' => 'design-studio-demo-lifestyle',
                'title' => 'Lifestyle Motion Still — Design Studio Demo',
                'description' => 'Demo licensed image for testing the Unclad Collection Design Studio library workflow.',
                'fixture' => 'lifestyle-video-poster.jpg',
                'width' => 1280,
                'height' => 720,
                'price_cents' => 1699,
                'sort_order' => 930,
                'days_ago' => 4,
            ],
            [
                'slug' => 'design-studio-demo-beach-story',
                'title' => 'Beach Story — Design Studio Demo',
                'description' => 'Demo licensed image for testing the Unclad Collection Design Studio library workflow.',
                'fixture' => 'mixed-media-preview.jpg',
                'width' => 1200,
                'height' => 800,
                'price_cents' => 1899,
                'sort_order' => 940,
                'days_ago' => 2,
            ],
        ];
    }
}
