<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBranch extends Model
{
    protected $table = 'product_branch';

    protected $fillable = [
        'product_id',
        'branch_id',
        'stock',
        'price',
        'stock_minimum',
        'stock_maximum',
        'minimum_sell',
        'minimum_purchase',
        'tax_rate_id',
        'unit_sale',
        'status',
        'expiration_date',
        'favorite',
        'duration_minutes',
        'supplier_id',
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
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
