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
     * 1. Lấy danh sách bài viết (Có tìm kiếm + Phân trang)
     */
    public function index(Request $request)
    {
        // Eager load 'topic' để lấy tên chủ đề, 'author' để lấy tên người tạo
        $query = Post::with(['topic', 'author']);

        // Tìm kiếm theo tiêu đề
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Lọc theo loại (post/page)
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Sắp xếp mới nhất trước
        $posts = $query->orderBy('created_at', 'desc')
                       ->paginate($request->limit ?? 10);

        return response()->json($posts);
    }

    /**
     * 2. API phụ: Lấy danh sách Topic cho Dropdown
     */
    public function getTopics()
    {
        $topics = Topic::select('id', 'name')
                       ->where('status', 1) // Chỉ lấy topic đang hoạt động
                       ->get();

        return response()->json([
            'status' => true,
            'data' => $topics
        ]);
    }

    /**
     * 3. Xem chi tiết 1 bài viết
     */
    public function show($id)
    {
        $post = Post::find($id);
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
            'title' => 'required|string|max:255',
            'topic_id' => 'nullable|exists:topics,id',
            'content' => 'required',
            'type' => 'required|in:post,page',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->all();

        // Tự động tạo Slug nếu không nhập
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        // Check trùng slug
        if (Post::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . time();
        }

        // Upload Ảnh
        if ($request->hasFile('image')) {
            // Lưu vào storage/app/public/uploads/posts
            $path = $request->file('image')->store('uploads/posts', 'public');
            $data['image'] = $path;
        }

        // Gán người tạo
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }

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
            'title' => 'required|string|max:255',
            'topic_id' => 'nullable|exists:topics,id',
            'content' => 'required',
            'type' => 'required|in:post,page',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = $request->except(['image']); // Loại image ra để xử lý riêng

        // Xử lý Slug (nếu người dùng thay đổi slug)
        if ($request->filled('slug') && $request->slug != $post->slug) {
             // Check trùng slug với bài khác
             if (Post::where('slug', $request->slug)->where('id', '!=', $id)->exists()) {
                 $data['slug'] = $request->slug . '-' . time();
             } else {
                 $data['slug'] = $request->slug;
             }
        }

        // Xử lý Ảnh mới
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            // Lưu ảnh mới
            $path = $request->file('image')->store('uploads/posts', 'public');
            $data['image'] = $path;
        }

        // Gán người cập nhật
        if (Auth::check()) {
            $data['updated_by'] = Auth::id();
        }

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
            return response()->json(['message' => 'Not found'], 404);
        }

        // Xóa ảnh khỏi ổ cứng
        if ($post->image && Storage::disk('public')->exists($post->image)) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa thành công'
        ]);
    }
}
