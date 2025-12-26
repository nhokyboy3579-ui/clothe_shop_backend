<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
    'order_id',
    'product_id',
    'product_name',
    'variant',
    'quantity',
    'price',
    'total'
];

    // --- BẮT BUỘC PHẢI CÓ HÀM NÀY ĐỂ DÙNG whereHas('order') ---
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
