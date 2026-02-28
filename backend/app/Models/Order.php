<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'total_amount',
        'delivery_address',
        'delivery_location',
        'phone',
        'latitude',
        'longitude',
        'notes',
        'status',
        'payment_status',
        'delivered_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'delivered_at' => 'datetime'
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    
    public function getFormattedOrderIdAttribute()
    {
        return strtoupper($this->order_id);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'delivered' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
}