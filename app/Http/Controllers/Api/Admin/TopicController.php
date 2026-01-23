<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    /**
     * 1. Lấy danh sách (Search + Filter Status + Pagination)
     */
    public function index(Request $request)
    {
        $query = Topic::query();

        // Tìm kiếm theo tên hoặc slug
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái (0: Ẩn, 1: Hiện) - Khớp với dropdown Frontend
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Sắp xếp: Ưu tiên thứ tự hiển thị (sort_order), sau đó là mới nhất
        $topics = $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit ?? 10);

        return response()->json($topics);
    }

    /**
     * 2. Xem chi tiết
     */
    public function show($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy chủ đề'], 404);
        }
        return response()->json(['status' => true, 'data' => $topic]);
    }

    /**
     * 3. Thêm mới chủ đề
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'status'     => 'required|in:0,1',
            'sort_order' => 'nullable|integer',
            'slug'       => 'nullable|string|max:255',
        ]);

        $data = $request->all();

        // Xử lý Slug: Nếu trống thì lấy theo name, nếu có thì format chuẩn slug
        $baseSlug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name);
        $slug = $baseSlug;

        // Vòng lặp kiểm tra trùng slug (Tránh lỗi SQL)
        $count = 1;
        while (Topic::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }
        $data['slug'] = $slug;

        // Gán người tạo
        $data['created_by'] = Auth::id() ?? 1;

        $topic = Topic::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Thêm chủ đề thành công',
            'data' => $topic
        ], 201);
    }

    /**
     * 4. Cập nhật chủ đề
     */
    public function update(Request $request, $id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Chủ đề không tồn tại'], 404);
        }

        $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:0,1',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        // Xử lý Slug khi cập nhật
        if ($request->filled('slug')) {
            $newSlug = Str::slug($request->slug);
            if ($newSlug !== $topic->slug) {
                $baseSlug = $newSlug;
                $count = 1;
                while (Topic::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                    $newSlug = $baseSlug . '-' . $count++;
                }
                $data['slug'] = $newSlug;
            }
        }

        // Gán người cập nhật
        $data['updated_by'] = Auth::id() ?? 1;

        $topic->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật chủ đề thành công',
            'data' => $topic
        ]);
    }

    /**
     * 5. Xóa chủ đề
     */
    public function destroy($id)
    {
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu'], 404);
        }

        // Gợi ý: Kiểm tra xem chủ đề có đang chứa bài viết (Post) không?
        // if ($topic->posts()->count() > 0) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Không thể xóa chủ đề đang có bài viết hoạt động!'
        //     ], 400);
        // }

        $topic->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa chủ đề thành công'
        ]);
    }
}
