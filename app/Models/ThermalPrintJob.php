<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThermalPrintJob extends Model
{
    protected $fillable = [
        'branch_id',
        'movement_id',
        'printer_branch_id',
        'printer_name',
        'status',
        'source',
        'ticket_text',
        'content_summary',
        'payload_hash',
        'attempts',
        'last_error',
        'last_attempt_at',
        'printed_at',
        'requested_by',
        'printed_by',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function printerBranch(): BelongsTo
    {
        return $this->belongsTo(PrinterBranch::class, 'printer_branch_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
