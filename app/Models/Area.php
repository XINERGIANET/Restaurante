<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'areas';

    protected $fillable = [
        'name',
        'deleted',
    ];

    public $timestamps = false;
    
    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}
