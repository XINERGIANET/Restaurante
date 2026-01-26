<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules'; 
    protected $fillable = ['name', 'icon', 'order_num'];

    public function menuOptions()
    {
        return $this->hasMany(MenuOption::class, 'module_id');
    }
}