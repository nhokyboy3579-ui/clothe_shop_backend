<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'name', 'image', 'link', 'position', 'sort_order',
        'description', 'status', 'created_by', 'updated_by'
    ];

    /**
     * Accessor: Trả về URL đầy đủ cho ảnh Banner.
     */
    public function getImageUrlAttribute()
    {
        $image = $this->image;

        if (!$image || trim($image) === '') {
            return 'https://placehold.co/600x200?text=No+Banner+Image';
        }

        // 1. Nếu đã là URL tuyệt đối (FIX cho dữ liệu được lưu mới)
        if (Str::startsWith($image, ['http://', 'https://'])) {
             return $image;
        }

        // 2. Nếu là đường dẫn tương đối (FIX cho dữ liệu cũ)
        if (Storage::disk('public')->exists($image)) {
            // Sử dụng Storage::url() hoặc asset('storage/...') đều đúng
            return Storage::disk('public')->url($image);
        }

        return 'https://placehold.co/600x200?text=File+Not+Found';
    }

    /**
     * Relationship: User đã tạo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => 'System'
        ]);
    }
}
