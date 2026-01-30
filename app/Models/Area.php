<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Area extends Model
{
    protected $fillable = [
        'name',
        'branch_id',
        'deleted',
    ];

    protected $casts = [
        'deleted' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $builder->where('deleted', false);
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
