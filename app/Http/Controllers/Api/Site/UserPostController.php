<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;

class UserPostController extends Controller
{
    /**
     * Lấy danh sách bài viết (Có thể lọc theo Topic Slug)
     */
    public function index(Request $request)
    {
        // Query cơ bản lấy bài viết active, loại post
        $query = Post::where('status', 1)
                     ->where('type', 'post')
                     ->with('topic:id,name,slug'); // Lấy kèm thông tin topic

        // Nếu có tham số 'topic_slug' truyền vào -> Lọc theo chủ đề
        if ($request->has('topic_slug') && $request->topic_slug) {
            $slug = $request->topic_slug;
            // Tìm bài viết có topic_id thuộc về topic có slug tương ứng
            $query->whereHas('topic', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        }

        // Lấy bài mới nhất trước, phân trang 6 bài/trang
        $posts = $query->orderBy('created_at', 'desc')->paginate(6);

        // Lấy thông tin Topic hiện tại (nếu đang lọc) để hiển thị tiêu đề trang
        $currentTopic = null;
        if ($request->has('topic_slug')) {
            $currentTopic = Topic::where('slug', $request->topic_slug)->select('name', 'slug')->first();
        }

        return response()->json([
            'status' => true,
            'data' => $posts,
            'topic' => $currentTopic
        ]);
    }

    /**
     * Xem chi tiết bài viết theo Slug
     */
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
                    ->where('status', 1)
                    ->with('topic:id,name,slug')
                    ->first();

        if (!$post) {
            return response()->json(['status' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $post
        ]);
    }
}
