<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;

class AttributeController extends Controller
{
    // 1. Danh sách
    public function index()
    {
        $attributes = Attribute::orderBy('id', 'desc')->paginate(10);
        return view('admin.attribute.index', compact('attributes'));
    }

    // 2. Form thêm mới
    public function create()
    {
        return view('admin.attribute.create');
    }

    // 3. Xử lý lưu
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2|unique:attributes,name'
        ], [
            'name.required' => 'Tên thuộc tính không được để trống',
            'name.unique' => 'Tên thuộc tính này đã tồn tại'
        ]);

        try {
            Attribute::create(['name' => $request->name]);
            return redirect()->route('attribute.index')->with('success', 'Thêm thuộc tính thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // 4. Form sửa (Mới thêm)
    public function edit($id)
    {
        $attribute = Attribute::find($id);
        if (!$attribute) {
            return redirect()->route('attribute.index')->with('error', 'Thuộc tính không tồn tại!');
        }
        return view('admin.attribute.edit', compact('attribute'));
    }

    // 5. Cập nhật (Mới thêm)
    public function update(Request $request, $id)
    {
        // Validate: Tên bắt buộc, duy nhất (nhưng bỏ qua ID hiện tại)
        $request->validate([
            'name' => 'required|min:2|unique:attributes,name,'.$id
        ], [
            'name.required' => 'Tên thuộc tính không được để trống',
            'name.unique' => 'Tên thuộc tính này đã tồn tại'
        ]);

        try {
            $attribute = Attribute::find($id);
            if (!$attribute) {
                return back()->with('error', 'Không tìm thấy dữ liệu!');
            }

            $attribute->update(['name' => $request->name]);

            return redirect()->route('attribute.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // 6. Xóa (Mới thêm)
    public function destroy($id)
    {
        try {
            $attribute = Attribute::find($id);
            if (!$attribute) {
                return back()->with('error', 'Không tìm thấy dữ liệu!');
            }

            // (Tùy chọn) Kiểm tra xem thuộc tính này đã dùng cho sản phẩm nào chưa
            // Nếu muốn chặt chẽ, bạn có thể uncomment đoạn này khi đã có model ProductAttribute
            /*
            if ($attribute->productAttributes()->count() > 0) {
                return back()->with('error', 'Không thể xóa! Thuộc tính này đang được sử dụng trong sản phẩm.');
            }
            */

            $attribute->delete();
            return redirect()->route('attribute.index')->with('success', 'Xóa thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
