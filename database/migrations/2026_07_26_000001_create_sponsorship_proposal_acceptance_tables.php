<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sponsorship_proposal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsorship_proposal_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('signer_name');
            $table->string('signer_title')->nullable();
            $table->string('signer_email');
            $table->string('signer_company')->nullable();
            $table->boolean('terms_acknowledged')->default(false);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('sponsorship_proposal_id', 'sp_accept_proposal_fk')
                ->references('id')
                ->on('sponsorship_proposals')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'sp_accept_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('sponsorship_proposal_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsorship_proposal_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->string('source')->default('admin');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('sponsorship_proposal_id', 'sp_history_proposal_fk')
                ->references('id')
                ->on('sponsorship_proposals')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'sp_history_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['sponsorship_proposal_id', 'created_at'],
                'proposal_status_history_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_proposal_status_histories');
        Schema::dropIfExists('sponsorship_proposal_acceptances');
    }
};
