<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str; // Import Str để tạo slug nếu cần

class CategoryController extends Controller
{
    // 1. Danh sách
    public function index()
    {
        $categories = Category::with('parent')
            ->withCount('products') // Đếm số sản phẩm
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('admin.category.index', compact('categories'));
    }

    // 2. Form thêm mới
    public function create()
    {
        // Lấy danh mục đang hoạt động để chọn làm cha
        $categories = Category::where('status', 1)->get();
        return view('admin.category.create', compact('categories'));
    }

    // 3. Xử lý lưu
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2|unique:categories,name',
            'slug' => 'required|unique:categories,slug',
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            $category = new Category();
            $category->fill($request->all());
            $category->created_by = 1;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/category'), $filename);
                $category->image = $filename;
            }

            $category->save();
            return redirect()->route('category.index')->with('success', 'Thêm danh mục thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    // 4. Form sửa (HOÀN THIỆN)
    public function edit($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return redirect()->route('category.index')->with('error', 'Danh mục không tồn tại!');
        }

        // Lấy danh sách cha: Phải loại bỏ chính nó ra khỏi danh sách (để tránh lỗi cha là chính mình)
        $categories = Category::where('id', '!=', $id)->where('status', 1)->get();

        return view('admin.category.edit', compact('category', 'categories'));
    }

    // 5. Cập nhật (HOÀN THIỆN)
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:2|unique:categories,name,'.$id,
            'slug' => 'required|unique:categories,slug,'.$id,
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            $category = Category::find($id);
            $category->fill($request->except('image'));
            $category->updated_by = 1;

            // Xử lý ảnh
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ
                if ($category->image && file_exists(public_path('images/category/' . $category->image))) {
                    unlink(public_path('images/category/' . $category->image));
                }

                // Upload mới
                $file = $request->file('image');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/category'), $filename);
                $category->image = $filename;
            }

            $category->save();
            return redirect()->route('category.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // 6. Xóa (HOÀN THIỆN - CÓ KIỂM TRA RÀNG BUỘC)
    public function destroy($id)
    {
        try {
            $category = Category::withCount(['products', 'children'])->find($id);

            if (!$category) {
                return back()->with('error', 'Danh mục không tồn tại!');
            }

            // Kiểm tra 1: Có danh mục con không?
            if ($category->children_count > 0) {
                return back()->with('error', 'Không thể xóa! Danh mục này đang chứa các danh mục con.');
            }

            // Kiểm tra 2: Có sản phẩm không?
            if ($category->products_count > 0) {
                return back()->with('error', 'Không thể xóa! Danh mục này đang chứa sản phẩm.');
            }

            // Xóa ảnh
            if ($category->image && file_exists(public_path('images/category/' . $category->image))) {
                unlink(public_path('images/category/' . $category->image));
            }

            $category->delete();
            return redirect()->route('category.index')->with('success', 'Xóa thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
}
