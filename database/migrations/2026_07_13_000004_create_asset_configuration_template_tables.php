<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_configuration_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('display_type', 32)->default('select');
            $table->boolean('is_required_default')->default(false);
            $table->boolean('allows_multiple_default')->default(false);
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
        });

        Schema::create('asset_configuration_template_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_configuration_template_id');
            $table->string('label');
            $table->string('value');
            $table->text('description')->nullable();
            $table->string('swatch_color', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->integer('price_adjustment_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(
                'asset_configuration_template_id',
                'actv_template_fk'
            )
                ->references('id')
                ->on('asset_configuration_templates')
                ->cascadeOnDelete();

            $table->unique(
                ['asset_configuration_template_id', 'value'],
                'configuration_template_values_unique'
            );

            $table->index(
                ['asset_configuration_template_id', 'is_active', 'sort_order'],
                'configuration_template_values_sort_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_configuration_template_values');
        Schema::dropIfExists('asset_configuration_templates');
    }
};
