<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'delivery_person_name', 'collector_phone', 'delivered_at'
    ];

    protected $casts = [
        'delivered_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}