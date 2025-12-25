<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 1. THÊM DÒNG NÀY (Import thư viện Sanctum)
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // 2. THÊM "HasApiTokens" VÀO TRONG DÒNG NÀY
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username', // Đảm bảo bạn đã thêm dòng này như hướng dẫn trước
        'email',
        'phone',    // Đảm bảo bạn đã thêm dòng này
        'password',
        'role',
        'status',
        'avatar',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
