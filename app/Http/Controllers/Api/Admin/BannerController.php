<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Import Str

class BannerController extends Controller
{
    /**
     * Lấy danh sách Banner (index)
     */
    public function index(Request $request)
    {
        try {
            $query = Banner::orderBy('sort_order', 'asc')->orderBy('id', 'desc');

            if ($request->has('search') && $request->search != '') {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $limit = $request->limit ?? 10;
            $attributes = $query->paginate($limit);

            // Accessor getImageUrlAttribute sẽ chạy để hiển thị ảnh (FIX)
            return response()->json($attributes);
        } catch (\Exception $e) {
            \Log::error("Banner Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải danh sách banner.'], 500);
        }
    }

    /**
     * Thêm mới Banner (STORE) - FIX: Lưu URL Tuyệt đối
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'required|in:slideshow,ads',
            'status' => 'required|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except('image');
        $data['created_by'] = $request->user() ? $request->user()->id : null;

        DB::beginTransaction();
        try {
            $relativePath = null;
            if ($imageFile) {
                // 1. Lưu file vào Storage (trả về đường dẫn tương đối)
                $relativePath = $imageFile->store('images/banners', 'public');

                // 2. SINH URL TUYỆT ĐỐI BẰNG STORAGE::URL()
                $absoluteUrl = Storage::disk('public')->url($relativePath);

                // LƯU FIX: Gán URL tuyệt đối vào cột image
                $data['image'] = $absoluteUrl;
            } else {
                 $data['image'] = null;
            }

            unset($data['_method']);
            $banner = Banner::create($data);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Thêm banner thành công!', 'banner' => $banner], 201);

        } catch (\Exception $e) {
            // Sử dụng relativePath để xóa file nếu có lỗi
            if (isset($relativePath)) { Storage::disk('public')->delete($relativePath); }
            DB::rollBack();
            \Log::error("Banner Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi thêm banner.'], 500);
        }
    }

    /**
     * Cập nhật Banner (UPDATE) - FIX: Lưu URL Tuyệt đối và Xóa file cũ
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'required|in:slideshow,ads',
            'status' => 'required|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except(['_method', 'image']);
        $originalImageUrl = $banner->image; // Đây là URL tuyệt đối cũ

        DB::beginTransaction();
        try {
            $relativePath = null;
            if ($imageFile) {
                // 1. Xóa ảnh cũ an toàn (Chuyển URL tuyệt đối thành path tương đối để xóa)
                if ($originalImageUrl) {
                    $pathToDelete = Str::after($originalImageUrl, asset('storage') . '/');
                    if (Storage::disk('public')->exists($pathToDelete)) {
                        Storage::disk('public')->delete($pathToDelete);
                    }
                }

                // 2. Lưu file mới và lấy URL tuyệt đối
                $relativePath = $imageFile->store('images/banners', 'public');
                $absoluteUrl = Storage::disk('public')->url($relativePath);

                $data['image'] = $absoluteUrl;
            } else {
                // Giữ nguyên URL tuyệt đối cũ
                $data['image'] = $originalImageUrl;
            }

            $data['updated_by'] = $request->user() ? $request->user()->id : null;

            $banner->update($data);
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Cập nhật banner thành công!', 'banner' => $banner]);

        } catch (\Exception $e) {
            // Sử dụng relativePath để xóa file nếu có lỗi
            if (isset($relativePath)) { Storage::disk('public')->delete($relativePath); }
            DB::rollBack();
            \Log::error("Banner Update Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi cập nhật banner.'], 500);
        }
    }

    /**
     * Xóa Banner (DESTROY) - FIX: Xóa file vật lý
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        try {
            // FIX: Cần chuyển đổi URL tuyệt đối thành Path tương đối để xóa file vật lý
            if ($banner->image) {
                 $pathToDelete = Str::after($banner->image, asset('storage') . '/');
                 Storage::disk('public')->delete($pathToDelete);
            }

            $banner->delete();
            return response()->json(['status' => true, 'message' => 'Xóa banner thành công!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi xóa banner.'], 500);
        }
    }
}
