<?php

use App\Models\Image;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(LicenseType::class)
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('USD');

            $table->timestamps();

            $table->unique([
                'user_id',
                'image_id',
                'license_type_id',
            ], 'cart_items_user_image_license_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};