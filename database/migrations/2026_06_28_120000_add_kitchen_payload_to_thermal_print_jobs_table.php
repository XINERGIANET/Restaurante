<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thermal_print_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('thermal_print_jobs', 'ticket_text')) {
                $table->longText('ticket_text')->nullable();
            }
            if (! Schema::hasColumn('thermal_print_jobs', 'content_summary')) {
                $table->text('content_summary')->nullable();
            }
            if (! Schema::hasColumn('thermal_print_jobs', 'payload_hash')) {
                $table->string('payload_hash', 64)->nullable();
            }
        });

        Schema::table('thermal_print_jobs', function (Blueprint $table) {
            $table->index(['branch_id', 'source', 'status'], 'thermal_jobs_branch_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('thermal_print_jobs', function (Blueprint $table) {
            $table->dropIndex('thermal_jobs_branch_source_status_idx');
            $table->dropColumn(['ticket_text', 'content_summary', 'payload_hash']);
        });
    }
};
