<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('hostname', 120)->nullable();
            $table->string('ip_address', 45);
            $table->string('location', 160)->nullable();
            $table->longText('qz_certificate')->nullable();
            $table->longText('qz_private_key')->nullable();
            $table->string('certificate_fingerprint', 64)->nullable();
            $table->timestamp('certificate_uploaded_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 1)->default('E');
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'ip_address']);
        });

        Schema::table('printers_branch', function (Blueprint $table) {
            $table->foreignId('print_station_id')->nullable()->after('branch_id')
                ->constrained('print_stations')->nullOnDelete();
            $table->string('connection_type', 12)->default('network')->after('print_station_id');
            $table->unsignedSmallInteger('port')->default(9100)->after('ip');
            $table->string('driver_name', 180)->nullable()->after('port');
            $table->string('location', 160)->nullable()->after('driver_name');
            $table->text('notes')->nullable()->after('location');
            $table->index(['branch_id', 'connection_type', 'status'], 'printers_branch_connection_idx');
        });

        DB::table('printers_branch')->whereNull('ip')->orWhere('ip', '')->update(['connection_type' => 'usb']);
    }

    public function down(): void
    {
        Schema::table('printers_branch', function (Blueprint $table) {
            $table->dropIndex('printers_branch_connection_idx');
            $table->dropConstrainedForeignId('print_station_id');
            $table->dropColumn(['connection_type', 'port', 'driver_name', 'location', 'notes']);
        });

        Schema::dropIfExists('print_stations');
    }
};
