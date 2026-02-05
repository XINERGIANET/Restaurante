<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'banks';
    protected $fillable = ['name', 'description', 'status'];
    public $timestamps = true;
    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}
