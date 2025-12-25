<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Dùng để tạo slug tự động

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::orderBy('id', 'desc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Lọc theo loại (post/page) nếu cần
        if ($request->has('type')) {
            $query->where('post_type', $request->type);
        }

        $limit = $request->input('limit', 10);
        $posts = $query->paginate($limit);

        return response()->json($posts);
    }

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'post_type' => 'required|in:post,page',
        ]);

        $post = Post::create([
            'topic_id'    => $request->topic_id ?? 0,
            'title'       => $request->title,
            'slug'        => Str::slug($request->title), // Tự động tạo slug
            'image'       => $request->image,
            'content'     => $request->content,
            'description' => $request->description,
            'post_type'   => $request->post_type,
            'status'      => $request->status ?? 1,
            'created_by'  => $request->user() ? $request->user()->id : 1,
        ]);

        return response()->json(['message' => 'Thêm bài viết thành công', 'data' => $post]);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title' => 'required',
            'post_type' => 'required|in:post,page',
        ]);

        $post->update([
            'topic_id'    => $request->topic_id ?? $post->topic_id,
            'title'       => $request->title,
            'slug'        => Str::slug($request->title),
            'image'       => $request->image,
            'content'     => $request->content,
            'description' => $request->description,
            'post_type'   => $request->post_type,
            'status'      => $request->status ?? 1,
            'updated_by'  => $request->user() ? $request->user()->id : 1,
        ]);

        return response()->json(['message' => 'Cập nhật thành công', 'data' => $post]);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(['message' => 'Đã xóa bài viết']);
    }
}
