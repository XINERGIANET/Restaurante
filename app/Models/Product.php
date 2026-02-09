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
     * Mutator para el campo image.
     * Guarda el path relativo de storage (ej. "product/xxx.jpg"). Convierte vacíos y rutas temporales a null.
     */
    public function setImageAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['image'] = null;
            return;
        }
        // Si es un objeto (ej. UploadedFile), no guardar
        if (is_object($value)) {
            $this->attributes['image'] = null;
            return;
        }
        if (! is_string($value)) {
            $this->attributes['image'] = null;
            return;
        }
        $value = trim($value);
        // Aceptar siempre paths de nuestro storage (relativos al disco public)
        if (str_starts_with($value, 'product/')) {
            $this->attributes['image'] = $value;
            return;
        }
        // No guardar rutas temporales de PHP/Windows
        if (str_contains($value, '\\Temp\\') || str_contains($value, 'C:\\Windows\\')
            || preg_match('/php\d+\.tmp$/i', $value)
            || ($value !== '' && str_contains($value, '/tmp/') && ! str_contains($value, 'product/'))) {
            $this->attributes['image'] = null;
            return;
        }
        $this->attributes['image'] = $value;
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
