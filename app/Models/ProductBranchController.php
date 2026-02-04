<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBranchController extends Model
{

    protected $fillable = [
        'product_id',
        'branch_id',
        'stock',
        'price',
        'stock_minimum',
        'stock_maximum',
        'minimum_sell',
        'minimum_purchase',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRates::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
