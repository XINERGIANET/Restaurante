<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tables';

    protected $fillable = [
        'name',
        'area_id',
        'deleted',
        'status',
        'opened_at',
    ];

    /**
     * Casts
     *
     * @var array
     */
    protected $casts = [
        'opened_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
