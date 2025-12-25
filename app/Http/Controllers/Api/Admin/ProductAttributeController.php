<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductAttribute;
use App\Models\Attribute; // Model định nghĩa loại (Màu, Size)
use Illuminate\Support\Facades\Validator;

class ProductAttributeController extends Controller
{
    // 1. Lấy danh sách thuộc tính của 1 sản phẩm cụ thể
    public function index($productId)
    {
        $attributes = ProductAttribute::with('attribute')
            ->where('product_id', $productId)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $attributes
        ]);
    }

    // 2. Thêm thuộc tính cho sản phẩm
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'attribute_id' => 'required|exists:attributes,id',
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Kiểm tra trùng lặp (VD: Đã có Size XL rồi thì không thêm nữa)
        $exists = ProductAttribute::where('product_id', $request->product_id)
            ->where('attribute_id', $request->attribute_id)
            ->where('value', $request->value)
            ->exists();

        if ($exists) {
            return response()->json(['status' => false, 'message' => 'Thuộc tính này đã tồn tại!'], 400);
        }

        $attr = ProductAttribute::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Thêm thuộc tính thành công!',
            'data' => $attr
        ]);
    }

    // 3. Xóa thuộc tính
    public function destroy($id)
    {
        $attr = ProductAttribute::find($id);
        if ($attr) {
            $attr->delete();
            return response()->json(['status' => true, 'message' => 'Đã xóa thuộc tính']);
        }
        return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu'], 404);
    }

    // 4. Lấy danh sách các loại thuộc tính (Màu, Size...) để đổ vào Select box
    public function getAttributeTypes() {
        $types = Attribute::all(); // Giả sử bạn đã có Model Attribute
        return response()->json(['status' => true, 'data' => $types]);
    }
}
