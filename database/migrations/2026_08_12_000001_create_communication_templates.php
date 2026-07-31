<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category', 80)->index();
            $table->text('description')->nullable();
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('variables')->nullable();
            $table->json('required_variables')->nullable();
            $table->boolean('is_transactional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_template_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->boolean('is_active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['email_template_id', 'revision_number'], 'email_template_revision_unique');
        });

        Schema::create('email_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email')->index();
            $table->string('subject');
            $table->string('status', 30)->default('pending')->index();
            $table->string('message_id')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['template_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
        Schema::dropIfExists('email_template_revisions');
        Schema::dropIfExists('email_templates');
    }
};
