<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Lấy danh sách danh mục (index) - Hỗ trợ tìm kiếm và phân trang
     */
    public function index(Request $request)
    {
        try {
            $query = Category::with('parent');

            // 1. Tìm kiếm theo tên
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // 2. Lọc theo trạng thái (0: Active, 1: Hidden)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 3. Xử lý sắp xếp động
            $allowedColumns = ['id', 'name', 'sort_order', 'created_at']; // Các cột cho phép sort
            $sortColumn = in_array($request->sort_column, $allowedColumns) ? $request->sort_column : 'sort_order';
            $sortDirection = in_array($request->sort_direction, ['asc', 'desc']) ? $request->sort_direction : 'asc';

            $query->orderBy($sortColumn, $sortDirection);

            // 4. Phân trang
            $limit = $request->integer('limit', 10);
            $categories = $query->paginate($limit);

            return response()->json($categories);
        } catch (\Exception $e) {
            \Log::error("Category Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải danh sách danh mục.'], 500);
        }
    }
    /**
     * Lấy chi tiết danh mục (show)
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json($category);
        } catch (\Exception $e) {
            \Log::error("Category Show Error for ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải chi tiết danh mục từ server.'], 500);
        }
    }

    /**
     * Thêm mới danh mục (STORE) - FIX: Lưu URL Tuyệt đối
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except('image');
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user() ? $request->user()->id : null;

        DB::beginTransaction();
        try {
            $relativePath = null;
            if ($imageFile) {
                // 1. Lưu file vào Storage (trả về đường dẫn tương đối)
                $relativePath = $imageFile->store('images/categories', 'public');

                // 2. SINH URL TUYỆT ĐỐI VÀ LƯU VÀO DB
                $absoluteUrl = Storage::disk('public')->url($relativePath);
                $data['image'] = $absoluteUrl;
            } else {
                $data['image'] = null;
            }

            unset($data['_method']);
            $category = Category::create($data);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Thêm danh mục thành công!', 'category' => $category], 201);
        } catch (\Exception $e) {
            if (isset($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
            DB::rollBack();
            \Log::error("Category Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi thêm danh mục.'], 500);
        }
    }

    /**
     * Cập nhật danh mục (UPDATE) - FIX: Lưu URL Tuyệt đối
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'parent_id' => 'nullable|exists:categories,id|different:id',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except(['_method', 'image']);
        $originalImageUrl = $category->image;

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        DB::beginTransaction();
        try {
            $relativePath = null;
            if ($imageFile) {
                // 1. Xóa ảnh cũ an toàn (Chuyển URL tuyệt đối thành Path tương đối để xóa)
                if ($originalImageUrl) {
                    $pathToDelete = Str::after($originalImageUrl, asset('storage') . '/');
                    if (Storage::disk('public')->exists($pathToDelete)) {
                        Storage::disk('public')->delete($pathToDelete);
                    }
                }

                // 2. Lưu file mới và lấy URL tuyệt đối
                $relativePath = $imageFile->store('images/categories', 'public');
                $absoluteUrl = Storage::disk('public')->url($relativePath);

                $data['image'] = $absoluteUrl; // LƯU URL TUYỆT ĐỐI
            } else {
                // Giữ nguyên URL tuyệt đối cũ
                $data['image'] = $originalImageUrl;
            }

            $category->update($data);
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Cập nhật danh mục thành công!', 'category' => $category]);
        } catch (\Exception $e) {
            if (isset($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
            DB::rollBack();
            \Log::error("Category Update Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi cập nhật danh mục.'], 500);
        }
    }

    /**
     * Xóa danh mục (DESTROY)
     */
    public function destroy($id)
    {
        $category = Category::withCount('children', 'products')->findOrFail($id);

        if ($category->children_count > 0) {
            return response()->json(['message' => 'Không thể xóa: Danh mục này còn danh mục con.'], 409);
        }
        if ($category->products_count > 0) {
            return response()->json(['message' => 'Không thể xóa: Danh mục này còn sản phẩm.'], 409);
        }

        DB::beginTransaction();
        try {
            // Xóa file vật lý bằng URL tuyệt đối
            if ($category->image) {
                $pathToDelete = Str::after($category->image, asset('storage') . '/');
                Storage::disk('public')->delete($pathToDelete);
            }

            $category->delete();
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Xóa danh mục thành công.', 'category_id' => $id]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Category Delete Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi xóa danh mục.'], 500);
        }
    }
}
