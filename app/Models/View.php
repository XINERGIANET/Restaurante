<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    protected $table = 'views';
    protected $fillable = ['name', 'status'];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}