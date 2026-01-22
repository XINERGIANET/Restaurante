<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashBox extends Model
{
    use HasFactory;

    protected $table = 'cash_box';

    protected $fillable = [
        'opening_amount',
        'closing_amount',
        'description',
        'date',
        'deleted',
    ];
}
