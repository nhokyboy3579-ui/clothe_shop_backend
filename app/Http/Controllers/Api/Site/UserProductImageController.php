<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class UserProductImageController extends Controller
{
    /**
     * Lấy danh sách ảnh gallery của một sản phẩm cụ thể
     */
    public function index($productId)
    {
        // Lấy tất cả ảnh trong bảng product_images theo product_id
        $images = ProductImage::where('product_id', $productId)->get();

        // Laravel sẽ tự động chạy accessor getImageUrlAttribute
        // và thêm field 'image_url' nhờ protected $appends trong Model
        return response()->json([
            'status' => true,
            'data' => $images
        ]);
    }
}
