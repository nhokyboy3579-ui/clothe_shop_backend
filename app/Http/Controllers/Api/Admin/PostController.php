<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * 1. Lấy danh sách bài viết (Có tìm kiếm, lọc theo Topic, Trạng thái + Phân trang)
     */
    public function index(Request $request)
    {
        // Eager load 'topic' và 'author' (user)
        $query = Post::with(['topic', 'author']);

        // --- BỘ LỌC TÌM KIẾM ---

        // Tìm kiếm theo tiêu đề (Title)
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Lọc theo Chủ đề (topic_id) - Khớp với dropdown filter ở Frontend
        if ($request->filled('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }

        // Lọc theo Trạng thái (status) - Khớp với dropdown filter ở Frontend
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Lọc theo loại (post/page)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sắp xếp mặc định: Mới nhất lên đầu
        $posts = $query->orderBy('created_at', 'desc')
            ->paginate($request->limit ?? 10);

        return response()->json($posts);
    }

    /**
     * 2. API phụ: Lấy danh sách Topic cho Dropdown
     */
    public function getTopics()
    {
        // Lấy tất cả topic để admin có thể gán bài viết
        $topics = Topic::select('id', 'name')
            ->where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($topics); // Trả về mảng để Frontend dễ map
    }

    /**
     * 3. Xem chi tiết 1 bài viết
     */
    public function show($id)
    {
        $post = Post::with(['topic'])->find($id);
        if (!$post) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy bài viết'], 404);
        }
        return response()->json(['status' => true, 'data' => $post]);
    }

    /**
     * 4. Thêm mới bài viết
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'topic_id' => 'nullable|exists:topics,id',
            'content'  => 'required',
            'type'     => 'required|in:post,page',
            'status'   => 'required|in:0,1',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->all();

        // Xử lý Slug
        $data['slug'] = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->title);

        // Kiểm tra trùng slug và tự thêm hậu tố nếu cần
        $originalSlug = $data['slug'];
        $count = 1;
        while (Post::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        // Xử lý Upload Ảnh
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads/posts', 'public');
            $data['image'] = $path;
        }

        // Gán người tạo từ Auth (nếu có)
        $data['created_by'] = Auth::id() ?? 1; // Mặc định 1 nếu chưa cài đặt middleware

        $post = Post::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Thêm bài viết thành công',
            'data' => $post
        ], 201);
    }

    /**
     * 5. Cập nhật bài viết
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Bài viết không tồn tại'], 404);
        }

        $request->validate([
            'title'    => 'required|string|max:255',
            'topic_id' => 'nullable|exists:topics,id',
            'content'  => 'required',
            'type'     => 'required|in:post,page',
            'status'   => 'required|in:0,1',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->except(['image']);

        // Cập nhật Slug nếu có thay đổi hoặc title thay đổi
        if ($request->filled('slug')) {
            $newSlug = Str::slug($request->slug);
            if ($newSlug !== $post->slug) {
                $originalSlug = $newSlug;
                $count = 1;
                while (Post::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                    $newSlug = $originalSlug . '-' . $count++;
                }
                $data['slug'] = $newSlug;
            }
        }

        // Xử lý Ảnh mới
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu tồn tại
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            $data['image'] = $request->file('image')->store('uploads/posts', 'public');
        }

        $data['updated_by'] = Auth::id() ?? 1;

        $post->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thành công',
            'data' => $post
        ]);
    }

    /**
     * 6. Xóa bài viết
     */
    public function destroy($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Không tìm thấy bài viết để xóa'], 404);
        }

        // Xóa ảnh vật lý
        if ($post->image && Storage::disk('public')->exists($post->image)) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa bài viết thành công'
        ]);
    }
}
