<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_ticket_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->morphs('related');
            $table->string('label')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['support_ticket_id', 'related_type', 'related_id'], 'support_ticket_relation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_relations');
    }
};
