<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PrintStation extends Model
{
    protected $fillable = [
        'branch_id', 'uuid', 'name', 'hostname', 'ip_address', 'location',
        'qz_certificate', 'qz_private_key', 'certificate_fingerprint',
        'certificate_uploaded_at', 'last_seen_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'qz_certificate' => 'encrypted',
            'qz_private_key' => 'encrypted',
            'certificate_uploaded_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PrintStation $station): void {
            $station->uuid ??= (string) Str::uuid();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function printers(): HasMany
    {
        return $this->hasMany(PrinterBranch::class, 'print_station_id');
    }

    public function hasCredentials(): bool
    {
        return filled($this->qz_certificate) && filled($this->qz_private_key);
    }
}
