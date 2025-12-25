<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageController extends Controller
{
    // 1. Hiển thị giao diện quản lý ảnh của 1 sản phẩm
    public function index($product_id)
    {
        $product = Product::findOrFail($product_id);

        // Lấy danh sách ảnh của sản phẩm này
        $productImages = ProductImage::where('product_id', $product_id)->get();

        return view('admin.product.image', compact('product', 'productImages'));
    }

    // 2. Xử lý upload nhiều ảnh
    public function store(Request $request, $product_id)
    {
        $request->validate([
            'images' => 'required',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048' // Validate từng ảnh trong mảng
        ], [
            'images.required' => 'Vui lòng chọn ít nhất một ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh.',
            'images.*.max' => 'Kích thước ảnh không được vượt quá 2MB.'
        ]);

        try {
            $product = Product::findOrFail($product_id);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    // Tạo tên file ngẫu nhiên để tránh trùng
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    // Di chuyển ảnh vào thư mục public/images/product/gallery
                    $file->move(public_path('images/product/gallery'), $filename);

                    // Lưu vào database
                    ProductImage::create([
                        'product_id' => $product_id,
                        'image' => $filename,
                        'alt' => $product->name, // Mặc định alt theo tên sản phẩm
                    ]);
                }
            }

            return back()->with('success', 'Đã tải ảnh lên thư viện thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi upload: ' . $e->getMessage());
        }
    }

    // 3. Xóa một ảnh
    public function destroy($id)
    {
        try {
            $image = ProductImage::findOrFail($id);

            // Xóa file vật lý trong thư mục
            if (file_exists(public_path('images/product/gallery/' . $image->image))) {
                unlink(public_path('images/product/gallery/' . $image->image));
            }

            // Xóa record trong DB
            $image->delete();

            return back()->with('success', 'Đã xóa ảnh khỏi thư viện!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi xóa ảnh: ' . $e->getMessage());
        }
    }
}
