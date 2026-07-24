<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table): void {
            $table->timestamp('redacted_at')->nullable()->after('is_customer_visible');
            $table->foreignId('redacted_by')->nullable()->after('redacted_at')->constrained('users')->nullOnDelete();
            $table->string('redaction_reason', 500)->nullable()->after('redacted_by');
            $table->index(['support_ticket_id', 'redacted_at'], 'st_attach_ticket_redacted_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->index(['status', 'priority', 'updated_at'], 'st_status_priority_updated_idx');
            $table->index(['assigned_user_id', 'status', 'updated_at'], 'st_assignee_status_updated_idx');
            $table->index(['category_id', 'status', 'created_at'], 'st_category_status_created_idx');
            $table->index(['resolved_at', 'closed_at'], 'st_resolved_closed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table): void {
            $table->dropIndex('st_attach_ticket_redacted_idx');
            $table->dropConstrainedForeignId('redacted_by');
            $table->dropColumn(['redacted_at', 'redaction_reason']);
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropIndex('st_status_priority_updated_idx');
            $table->dropIndex('st_assignee_status_updated_idx');
            $table->dropIndex('st_category_status_created_idx');
            $table->dropIndex('st_resolved_closed_idx');
        });
    }
};
