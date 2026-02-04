<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRates extends Model
{
    protected $fillable = [
        'code',
        'description',
        'rate',
        'order_num',
        'is_active',
    ];

    public function productBranch()
    {
        return $this->hasMany(ProductBranchController::class);
    }
}
