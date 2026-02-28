<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_quantity',
        'category_id',
        'image_url',
        'featured',
        'is_active'
    ];


    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'featured' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

   
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getFormattedPriceAttribute()
    {
        return 'MWK ' . number_format($this->price, 2);
    }


    public function isInStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    public function decreaseStock($quantity)
    {
        if ($this->stock >= $quantity) {
            $this->stock -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function increaseStock($quantity)
    {
        $this->stock += $quantity;
        $this->save();
        return true;
    }
}