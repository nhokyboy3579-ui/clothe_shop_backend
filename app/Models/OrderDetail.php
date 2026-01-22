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
        'order_id',
        'product_id',
        'product_name', // Snapshot tên
        'variant',      // Snapshot biến thể (JSON string từ Next.js)
        'quantity',
        'price',        // Giá lúc mua
        'total'         // Thành tiền dòng này
    ];

    /**
     * Liên kết ngược lại đơn hàng chính
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Liên kết với sản phẩm gốc (để lấy ảnh hoặc link)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
