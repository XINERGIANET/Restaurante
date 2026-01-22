<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    protected $fillable = [
        'table_id',
        'employee_id',
        'number_persons',
        'status',
        'deleted',
        'delivery',
    ];

    protected $casts = [
        'delivery' => 'array', 
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
