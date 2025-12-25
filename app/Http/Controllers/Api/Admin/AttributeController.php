<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AttributeController extends Controller
{
    /**
     * Lấy danh sách Attribute (index) - Hỗ trợ tìm kiếm và phân trang
     */
    public function index(Request $request)
    {
        try {
            $query = Attribute::orderBy('name', 'asc');

            if ($request->has('search') && $request->search != '') {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Lấy tham số limit/page
            $limit = $request->limit ?? 10;
            $page = $request->page ?? 1;

            // Phân trang
            $attributes = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json($attributes);
        } catch (\Exception $e) {
            \Log::error("Attribute Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải danh sách thuộc tính.'], 500);
        }
    }

    /**
     * Thêm mới Attribute (STORE)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $attribute = Attribute::create($request->only('name'));
            return response()->json(['status' => true, 'message' => 'Thêm thuộc tính thành công!', 'attribute' => $attribute], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server khi thêm thuộc tính.'], 500);
        }
    }

    /**
     * Cập nhật Attribute (UPDATE)
     */
    public function update(Request $request, $id)
    {
        try {
            $attribute = Attribute::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255', Rule::unique('attributes')->ignore($attribute->id)],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // LƯU Ý: Phải sử dụng $request->only() để tránh lỗi Mass Assignment
            $attribute->update($request->only('name'));
            return response()->json(['status' => true, 'message' => 'Cập nhật thành công!', 'attribute' => $attribute]);
        } catch (ModelNotFoundException $e) {
             return response()->json(['message' => 'Không tìm thấy thuộc tính.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server khi cập nhật thuộc tính.'], 500);
        }
    }

    /**
     * Xóa Attribute (DESTROY)
     */
    public function destroy($id)
    {
        try {
            // Kiểm tra ràng buộc (Attribute có thể bị ràng buộc bởi ProductAttribute)
            $attribute = Attribute::withCount('productAttributes')->findOrFail($id);

            if ($attribute->product_attributes_count > 0) {
                return response()->json(['message' => 'Không thể xóa: Vẫn còn giá trị biến thể sử dụng thuộc tính này.'], 409);
            }

            $attribute->delete();
            return response()->json(['status' => true, 'message' => 'Xóa thuộc tính thành công!']);
        } catch (ModelNotFoundException $e) {
             return response()->json(['message' => 'Không tìm thấy thuộc tính.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi xóa thuộc tính.'], 500);
        }
    }
}
