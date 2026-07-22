<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advertiser_memberships', function (Blueprint $table) {
            $table->id();
            // The advertisers table is created by a later-dated migration.
            // Add this foreign key in the follow-up migration after advertisers exists.
            $table->unsignedBigInteger('advertiser_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->default('report_viewer');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['advertiser_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['advertiser_id', 'role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertiser_memberships');
    }
};
