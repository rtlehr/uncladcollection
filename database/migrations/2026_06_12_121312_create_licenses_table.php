<?php

use App\Models\Image;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Order::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(OrderItem::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(LicenseType::class)
                ->constrained()
                ->restrictOnDelete();

            $table->string('license_key')->unique();

            $table->string('status')->default('active');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('downloads_used')->default(0);

            /*
             * Historical snapshots
             */
            $table->string('license_name');
            $table->longText('license_terms')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'image_id']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};