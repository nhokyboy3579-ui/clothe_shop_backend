<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'note',
        'payment_method',
        'payment_status',
        'subtotal',
        'shipping_fee',
        'total_amount',
        'status',
        // Đã xóa 'created_by' vì không có trong Schema Migration của bạn
    ];

    // Định nghĩa các hằng số trạng thái
    const STATUS_NEW = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_SHIPPING = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_CANCELLED = 5;

    // --- RELATIONSHIPS ---

    /**
     * Người đặt hàng (Sử dụng user_id hiện có trong Schema)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'Khách vãng lai'
        ]);
    }

    /**
     * Chi tiết đơn hàng
     */
    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    // --- ACCESSORS ---

    /**
     * Chuyển đổi mã trạng thái sang tên hiển thị (Dùng cho Admin/User UI)
     */
    public function getStatusNameAttribute(): string
    {
        return match ((int)$this->status) {
            self::STATUS_NEW => 'Mới / Chờ xác nhận',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => 'Không xác định',
        };
    }
}
