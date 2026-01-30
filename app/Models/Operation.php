<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operations';
    protected $fillable = ['name', 'icon', 'action', 'color', 'status'];

    //public function views()
    //{
    //    return $this->belongsTo(View::class, 'view_id');
    //}
}
