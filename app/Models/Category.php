<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name', 'slug', 'image', 'parent_id',
        'sort_order', 'description', 'status',
        'created_by', 'updated_by'
    ];

    /**
     * Accessor: Trả về URL đầy đủ cho ảnh Danh mục.
     */
    public function getImageUrlAttribute()
    {
        $imagePath = $this->image;

        if (!$imagePath || trim($imagePath) === '') {
            return 'https://placehold.co/100x100?text=No+Img';
        }

        // FIX CHÍNH: Nếu đã là URL tuyệt đối (như cách Controller sẽ lưu)
        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        // Nếu là đường dẫn tương đối (Dữ liệu cũ)
        if (Storage::disk('public')->exists($imagePath)) {
            // FIX: Sử dụng Storage::url() để sinh URL tuyệt đối (dựa vào APP_URL)
            return Storage::disk('public')->url($imagePath);
        }

        return 'https://placehold.co/100x100?text=No+Img';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
