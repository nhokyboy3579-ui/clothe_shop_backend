<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\User;

class ProductStore extends Model
{
    use HasFactory;

    protected $table = 'product_store';

    protected $fillable = [
        'product_id',
        'price_root', // Giá nhập
        'qty',        // Số lượng
        'status',
        'created_by'
    ];

    // Quan hệ với bảng Product (Sản phẩm)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Quan hệ với bảng User (Người nhập kho)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
