<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\ProductAttribute;

class ProductAttributeController extends Controller
{
    // 1. Hiển thị trang quản lý thuộc tính của 1 sản phẩm
    public function index($product_id)
    {
        $product = Product::findOrFail($product_id);

        // Lấy danh sách thuộc tính đã gán cho sản phẩm này
        $productAttributes = ProductAttribute::where('product_id', $product_id)
                            ->with('attribute')
                            ->orderBy('attribute_id')
                            ->get();

        // Lấy tất cả thuộc tính có sẵn (Màu, Size...) để đổ vào Dropdown cho user chọn
        $attributes = Attribute::all();

        return view('admin.product.attribute', compact('product', 'productAttributes', 'attributes'));
    }

    // 2. Lưu thuộc tính mới
    public function store(Request $request, $product_id)
    {
        $request->validate([
            'attribute_id' => 'required',
            'value' => 'required|string|max:255'
        ]);

        try {
            // Kiểm tra xem cặp (Sản phẩm + Thuộc tính + Giá trị) đã tồn tại chưa để tránh trùng lặp
            // Ví dụ: Đã có Màu Đỏ rồi thì không thêm Màu Đỏ nữa
            $exists = ProductAttribute::where('product_id', $product_id)
                        ->where('attribute_id', $request->attribute_id)
                        ->where('value', $request->value)
                        ->exists();

            if ($exists) {
                return back()->with('error', 'Thuộc tính giá trị này đã tồn tại!');
            }

            ProductAttribute::create([
                'product_id' => $product_id,
                'attribute_id' => $request->attribute_id,
                'value' => $request->value
            ]);

            return back()->with('success', 'Đã thêm thuộc tính thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // 3. Xóa thuộc tính
    public function destroy($id)
    {
        try {
            ProductAttribute::destroy($id);
            return back()->with('success', 'Đã xóa thuộc tính!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
