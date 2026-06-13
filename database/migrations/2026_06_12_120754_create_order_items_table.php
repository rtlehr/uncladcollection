<?php

use App\Models\Image;
use App\Models\LicenseType;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Order::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(LicenseType::class)
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('pending');

            $table->unsignedInteger('quantity')->default(1);

            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('total_price_cents');

            /*
             * Snapshot fields
             * Preserve historical data if image or license changes later.
             */
            $table->string('image_title');
            $table->string('license_name');

            $table->longText('license_terms')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['order_id', 'image_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};