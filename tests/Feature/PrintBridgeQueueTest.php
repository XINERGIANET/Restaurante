<?php

namespace Tests\Feature;

use App\Services\PrintBridgeQueue;
use Illuminate\Support\Facades\Cache;
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
}
