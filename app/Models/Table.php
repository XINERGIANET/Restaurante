<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name',
        'status',
        'opened_at',
        'area_id',
        'branch_id',
        'deleted',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
