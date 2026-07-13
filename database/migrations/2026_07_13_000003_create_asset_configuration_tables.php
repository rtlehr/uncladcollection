<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_configuration_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('display_type', 32)->default('select');
            $table->boolean('is_required')->default(false);
            $table->boolean('allows_multiple')->default(false);
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->decimal('minimum_value', 14, 4)->nullable();
            $table->decimal('maximum_value', 14, 4)->nullable();
            $table->decimal('step_value', 14, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['asset_id', 'code'], 'asset_configuration_groups_asset_code_unique');
            $table->index(['asset_id', 'is_active', 'sort_order'], 'asset_configuration_groups_asset_sort_index');
        });

        Schema::create('asset_configuration_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_configuration_group_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->text('description')->nullable();
            $table->string('swatch_color', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['asset_configuration_group_id', 'value'], 'asset_configuration_values_group_value_unique');
            $table->index(['asset_configuration_group_id', 'is_active', 'sort_order'], 'asset_configuration_values_group_sort_index');
        });

        Schema::create('asset_configuration_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_configuration_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_type', 32)->default('fixed_adjustment');
            $table->integer('amount_cents')->default(0);
            $table->decimal('percentage', 8, 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_configuration_selections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_key')->nullable()->index();
            $table->json('selections');
            $table->unsignedInteger('base_price_cents')->default(0);
            $table->integer('price_adjustment_cents')->default(0);
            $table->unsignedInteger('total_price_cents')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_configuration_selections');
        Schema::dropIfExists('asset_configuration_rules');
        Schema::dropIfExists('asset_configuration_values');
        Schema::dropIfExists('asset_configuration_groups');
    }
};
