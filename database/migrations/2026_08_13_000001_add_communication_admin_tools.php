<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_settings', function (Blueprint $table) {
            $table->id();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('default_test_recipient')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('email_delivery_logs', function (Blueprint $table) {
            $table->foreignId('retried_from_id')->nullable()->after('id')
                ->constrained('email_delivery_logs')->nullOnDelete();
            $table->unsignedInteger('retry_count')->default(0)->after('status');
            $table->index(['status', 'created_at'], 'email_delivery_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('email_delivery_logs', function (Blueprint $table) {
            $table->dropIndex('email_delivery_status_created_index');
            $table->dropConstrainedForeignId('retried_from_id');
            $table->dropColumn('retry_count');
        });

        Schema::dropIfExists('communication_settings');
    }
};
