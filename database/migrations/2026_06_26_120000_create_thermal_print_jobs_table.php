<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thermal_print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('movement_id')->constrained('movements')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('printer_branch_id')->nullable()->constrained('printers_branch')->nullOnDelete();
            $table->string('printer_name', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('source', 40)->default('sale_ticket');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['movement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thermal_print_jobs');
    }
};
