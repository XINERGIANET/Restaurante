<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'abbreviation',
        'type',
        'category_id',
        'base_unit_id',
        'kardex',
        'is_compound',
        'image',
        'complement',
        'complement_mode',
        'classification',
        'features',
    ];

    /**
     * Mutator para el campo image
     * Convierte strings vacíos a null
     */
    public function setImageAttribute($value)
    {
        $this->attributes['image'] = empty($value) ? null : $value;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function productBranches()
    {
        return $this->hasMany(ProductBranch::class);
    }
}
