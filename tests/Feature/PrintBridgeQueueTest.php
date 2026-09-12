<?php

namespace Tests\Feature;

use App\Http\Controllers\PrintBridgeController;
use App\Services\PrintBridgeQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrintBridgeQueueTest extends TestCase
{
    public function test_queue_deduplicates_and_returns_thermal_job_metadata_on_ack(): void
    {
        Cache::flush();
        $queue = app(PrintBridgeQueue::class);

        $queue->push(9, 'BARRA2', 'first-payload', ['thermal_print_job_id' => 77]);
        $queue->push(9, 'BARRA2', 'duplicate-payload', ['thermal_print_job_id' => 77]);

        $queued = $queue->peek(9, 'BARRA2');
        $this->assertNotNull($queued);
        $this->assertSame(77, $queued['thermal_print_job_id']);
        $this->assertSame('first-payload', base64_decode($queued['b64'], true));

        $acknowledged = $queue->ack(9, 'BARRA2', $queued['id']);
        $this->assertSame(77, $acknowledged['thermal_print_job_id']);
        $this->assertNull($queue->peek(9, 'BARRA2'));
        $this->assertNull($queue->ack(9, 'BARRA2', $queued['id']));
    }

    public function test_station_error_ack_remains_pending_and_visible_as_an_error(): void
    {
        Schema::create('thermal_print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('movement_id');
            $table->string('printer_name')->nullable();
            $table->string('source');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->timestamps();
        });

        $jobId = DB::table('thermal_print_jobs')->insertGetId([
            'branch_id' => 9,
            'movement_id' => 25,
            'printer_name' => 'BARRA2',
            'source' => 'kitchen_order',
            'status' => 'printing',
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->put('branch_id', 9);
        $request = Request::create('/print-bridge/ack', 'POST', [
            'printer_name' => 'BARRA2',
            'job_id' => 'thermal:'.$jobId,
            'status' => 'error',
            'error_message' => 'QZ Tray desconectado',
        ]);
        $request->setLaravelSession(session()->driver());

        $response = app(PrintBridgeController::class)->ack($request, app(PrintBridgeQueue::class));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('thermal_print_jobs', [
            'id' => $jobId,
            'status' => 'pending',
            'last_error' => 'QZ Tray desconectado',
        ]);
    }
}
