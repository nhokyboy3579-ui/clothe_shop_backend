<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'image', 'alt'];

    // --- QUAN TRỌNG: Dòng này bảo Laravel luôn gửi kèm field 'image_url' ---
    protected $appends = ['image_url'];

    /**
     * Accessor: Tự động biến đổi path trong DB thành Full URL
     * Khi gọi $productImage->image_url hoặc trả về JSON, nó sẽ chạy hàm này
     */
    public function getImageUrlAttribute()
    {
        // $this->image chính là chuỗi "products/gallery/..." trong DB

        // Kiểm tra xem ảnh có chuỗi http chưa (đề phòng ảnh copy từ mạng)
        if (strpos($this->image, 'http') === 0) {
            return $this->image;
        }

        // Kiểm tra file có thật trong ổ cứng không (Optional)
        // Nếu muốn nhanh có thể bỏ qua if exists và return luôn dòng asset
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            // Hàm asset('storage/...') sẽ tự động thêm http://localhost:8000
            return asset('storage/' . $this->image);
        }

        // Ảnh mặc định nếu không tìm thấy file
        return 'https://placehold.co/400?text=No+Img';
    }
}
