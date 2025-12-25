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
     * Lấy danh sách (Search + Pagination)
     */
    public function index(Request $request)
    {
        $query = Topic::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sắp xếp theo thứ tự hiển thị (sort_order) hoặc ngày tạo
        $topics = $query->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->paginate($request->limit ?? 10);

        return response()->json($topics);
    }

    /**
     * Chi tiết
     */
    public function show($id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['status' => true, 'data' => $topic]);
    }

    /**
     * Thêm mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:0,1',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        // Xử lý Slug
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        if (Topic::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . time();
        }

        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }

        $topic = Topic::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Thêm chủ đề thành công',
            'data' => $topic
        ], 201);
    }

    /**
     * Cập nhật
     */
    public function update(Request $request, $id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['message' => 'Not found'], 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $data = $request->all();

        // Xử lý Slug khi update
        if ($request->filled('slug') && $request->slug != $topic->slug) {
             if (Topic::where('slug', $request->slug)->where('id', '!=', $id)->exists()) {
                 $data['slug'] = $request->slug . '-' . time();
             }
        }

        if (Auth::check()) {
            $data['updated_by'] = Auth::id();
        }

        $topic->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thành công',
            'data' => $topic
        ]);
    }

    /**
     * Xóa
     */
    public function destroy($id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['message' => 'Not found'], 404);

        // Kiểm tra xem có bài viết nào thuộc chủ đề này không (Optional)
        // if ($topic->posts()->exists()) {
        //     return response()->json(['message' => 'Không thể xóa chủ đề đang chứa bài viết'], 400);
        // }

        $topic->delete();

        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}
