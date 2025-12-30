<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword; // Thư viện hỗ trợ Reset Password
use Laravel\Sanctum\HasApiTokens; // Thư viện hỗ trợ Token API

class User extends Authenticatable
{
    /**
     * HasApiTokens: Cho phép tạo token đăng nhập cho React/Next.js
     * HasFactory: Hỗ trợ tạo dữ liệu mẫu (Seeder/Factory)
     * Notifiable: Cho phép gửi thông báo (Email, SMS)
     * CanResetPassword: Cho phép nhận link khôi phục mật khẩu qua Email
     */
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    /**
     * Các trường có thể điền thông tin (Mass Assignable)
     * Lưu ý: Tôi đã thêm các trường cần thiết theo Model "Order" và các quan hệ của bạn.
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',      // Phân quyền: admin, customer
        'status',    // Trạng thái: 0: khóa, 1: hoạt động
        'avatar',
        'created_by',
    ];

    /**
     * Các trường sẽ bị ẩn khi API trả về dữ liệu (đảm bảo bảo mật)
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Ép kiểu dữ liệu (Casting)
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Tự động mã hóa mật khẩu khi lưu
        ];
    }

    /**
     * Mối quan hệ với Model Order
     * Một người dùng có thể có nhiều đơn hàng
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
