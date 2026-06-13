<?php

use App\Models\Download;
use App\Models\Image;
use App\Models\License;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(License::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(OrderItem::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('download_type')->default('high_res');

            $table->ipAddress('ip_address')->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamp('downloaded_at');

            $table->timestamps();

            $table->index(['user_id', 'downloaded_at']);
            $table->index(['image_id', 'downloaded_at']);
            $table->index(['license_id', 'downloaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};