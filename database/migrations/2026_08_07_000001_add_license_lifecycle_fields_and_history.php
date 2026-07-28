<?php

use App\Models\License;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->unsignedInteger('terms_version')->nullable()->after('license_terms');
            $table->index(['user_id', 'status', 'expires_at'], 'licenses_user_status_expiry_index');
        });

        Schema::create('license_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(License::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->text('customer_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['license_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_status_histories');

        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropIndex('licenses_user_status_expiry_index');
            $table->dropColumn(['status_reason', 'status_changed_at', 'terms_version']);
        });
    }
};
