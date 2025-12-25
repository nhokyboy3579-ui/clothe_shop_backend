<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'topics';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    // Tự động tạo slug khi tạo mới
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name) . '-' . time();
            }
        });
    }
}
