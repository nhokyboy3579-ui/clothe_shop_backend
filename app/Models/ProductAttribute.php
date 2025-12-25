<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'value'
    ];

    // Quan hệ với Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Quan hệ với bảng Attributes gốc (Nơi định nghĩa tên: Color, Size...)
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
