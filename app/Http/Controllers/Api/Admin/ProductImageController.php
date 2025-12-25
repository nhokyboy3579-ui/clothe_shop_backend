<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Lấy danh sách ảnh của 1 sản phẩm
     * GET: /api/admin/product-images/{productId}
     */
    public function index($productId)
    {
        $images = ProductImage::where('product_id', $productId)->get();
        return response()->json($images);
    }

    /**
     * Upload nhiều ảnh
     * POST: /api/admin/product-images/{productId}
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120' // Max 5MB
        ]);

        $uploadedImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                // Lưu vào folder: storage/app/public/products/gallery
                $path = $file->store('products/gallery', 'public');

                $image = ProductImage::create([
                    'product_id' => $productId,
                    'image' => $path,
                    'alt' => $file->getClientOriginalName()
                ]);

                // Append URL để frontend hiển thị ngay
                $image->url = $image->image_url;
                $uploadedImages[] = $image;
            }
        }

        return response()->json([
            'message' => 'Upload thành công',
            'data' => $uploadedImages
        ], 201);
    }

    /**
     * Xóa 1 ảnh
     * DELETE: /api/admin/product-images/{id}
     */
    public function destroy($id)
    {
        try {
            $image = ProductImage::findOrFail($id);

            // Xóa file trong ổ đĩa
            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }

            // Xóa trong database
            $image->delete();

            return response()->json(['message' => 'Đã xóa ảnh']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi xóa ảnh'], 500);
        }
    }
}
