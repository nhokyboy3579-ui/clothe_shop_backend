<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'order_details';

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'variant',
        'quantity', 'price', 'total'
    ];

    // Bắt buộc phải có khi tải chi tiết
    protected $with = ['product'];

    /**
     * Liên kết với đơn hàng
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Liên kết với sản phẩm gốc (có thể null nếu sản phẩm bị xóa)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withDefault([
            'name' => 'Sản phẩm gốc đã bị xóa'
        ]);
    }
}
