<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_id',
        'ruc',
        'company_id',
        'legal_name',
        'logo',
        'address',
        'location_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
