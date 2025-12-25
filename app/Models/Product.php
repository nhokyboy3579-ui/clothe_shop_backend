<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// --- IMPORT ĐẦY ĐỦ CÁC MODEL LIÊN QUAN ---
use App\Models\Category;
use App\Models\ProductSale;
use App\Models\ProductImage;
use App\Models\ProductAttribute; // Đã thêm dòng này để tránh lỗi

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'thumbnail',
        'content',
        'description',
        'price_buy',
        'status',
        'created_by',
        'updated_by'
    ];

    // Tự động tải category khi query Product để tiện sử dụng
    protected $with = ['category'];

    /**
     * Accessor: Tự động trả về Link ảnh đầy đủ
     * (Giúp bạn không cần xử lý thủ công trong Controller nữa nếu muốn dùng $product->thumbnail_url)
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $image = $attributes['thumbnail'] ?? null;

                if (!$image || trim($image) === '') return 'https://placehold.co/100x100?text=No+Image';

                if (Str::contains($image, 'http') || Str::contains($image, 'data:image')) {
                    return $image;
                }

                if (Storage::disk('public')->exists($image)) {
                    return asset('storage/' . $image);
                }

                return 'https://placehold.co/100x100?text=No+Image';
            }
        );
    }

    // --- RELATIONSHIPS (CÁC MỐI QUAN HỆ) ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withDefault([
            'name' => 'Danh mục đã bị xóa'
        ]);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /**
     * Quan hệ với bảng Sale.
     * LƯU Ý: Đã bỏ điều kiện check ngày tháng ở đây để đảm bảo dữ liệu luôn hiện ra khi test.
     * Logic check ngày nên để ở Controller hoặc Scope riêng.
     */
    public function sale(): HasOne
    {
        return $this->hasOne(ProductSale::class, 'product_id');
            // ->where('status', 1); // Có thể mở lại dòng này nếu muốn chỉ lấy sale đang active
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    // --- SCOPES (BỘ LỌC) ---

    public function scopeFilter($query)
    {
        if (request('keyword')) {
            $key = request('keyword');
            $query->where('name', 'LIKE', "%{$key}%");
        }
        if (request('category_id')) {
            $query->where('category_id', request('category_id'));
        }

        // Sắp xếp
        if (request('sort')) {
            $sort = request('sort');
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('price_buy', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price_buy', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
