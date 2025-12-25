<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSale extends Model
{
    use HasFactory;

    protected $table = 'product_sales';

    protected $fillable = [
        'product_id',
        'price_sale',
        'date_begin',
        'date_end',
        'status',
        'created_by'
    ];

    protected $casts = [
        'date_begin' => 'datetime',
        'date_end' => 'datetime',
        'price_sale' => 'decimal:2',
    ];

    /**
     * Quan hệ: Sale thuộc về 1 Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withDefault([
            'name' => 'Sản phẩm đã xóa',
            'price_buy' => 0
        ]);
    }
}
