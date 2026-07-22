<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advertisers', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('name'); $table->string('slug')->unique();
            $table->string('status')->default('active')->index(); $table->string('website_url')->nullable();
            $table->string('billing_email')->nullable(); $table->string('contact_name')->nullable(); $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable(); $table->text('billing_address')->nullable(); $table->text('notes')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('name'); $table->string('code')->unique();
            $table->string('location'); $table->string('format')->default('banner'); $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable(); $table->unsignedInteger('max_active_campaigns')->default(1);
            $table->unsignedBigInteger('base_price_cents')->default(0); $table->string('pricing_model')->default('flat');
            $table->json('eligibility_rules')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('advertising_campaigns', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->foreignId('advertiser_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('public_code')->unique(); $table->string('status')->default('draft')->index();
            $table->string('objective')->default('awareness'); $table->string('pricing_model')->default('flat');
            $table->unsignedBigInteger('budget_cents')->default(0); $table->unsignedBigInteger('contract_value_cents')->default(0);
            $table->unsignedBigInteger('impression_goal')->nullable(); $table->unsignedBigInteger('click_goal')->nullable();
            $table->timestamp('starts_at')->nullable()->index(); $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('submitted_at')->nullable(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable(); $table->text('internal_notes')->nullable(); $table->timestamps(); $table->softDeletes();
            $table->index(['advertiser_id','status']); $table->index(['status','starts_at','ends_at']);
        });
        Schema::create('advertising_campaign_placement', function (Blueprint $table) {
            $table->id(); $table->foreignId('advertising_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('priority')->default(0);
            $table->unsignedBigInteger('allocated_budget_cents')->default(0); $table->timestamps();
            $table->unique(['advertising_campaign_id','ad_placement_id'],'ad_campaign_placement_unique');
        });
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->foreignId('advertising_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('creative_type')->default('image'); $table->string('status')->default('draft')->index();
            $table->string('media_path')->nullable(); $table->string('mime_type')->nullable(); $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable(); $table->unsignedInteger('height')->nullable(); $table->string('headline')->nullable();
            $table->text('body')->nullable(); $table->string('cta_label')->nullable(); $table->string('destination_url',1000)->nullable();
            $table->text('alt_text')->nullable(); $table->timestamp('approved_at')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable(); $table->timestamps(); $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ad_creatives'); Schema::dropIfExists('advertising_campaign_placement');
        Schema::dropIfExists('advertising_campaigns'); Schema::dropIfExists('ad_placements'); Schema::dropIfExists('advertisers');
    }
};