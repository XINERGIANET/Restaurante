<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // El bloqueo se guarda en la mesa para que funcione entre navegadores y equipos.
            $table->unsignedBigInteger('attending_user_id')->nullable()->index()->after('opened_at');
            $table->string('attending_waiter_name')->nullable()->after('attending_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex(['attending_user_id']);
            $table->dropColumn(['attending_user_id', 'attending_waiter_name']);
        });
    }
};
