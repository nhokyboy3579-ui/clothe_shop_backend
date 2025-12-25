<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;

class UserTopicController extends Controller
{
    // Lấy danh sách chủ đề (để hiển thị ở Footer)
    public function index()
    {
        $topics = Topic::where('status', 1) // Chỉ lấy active
                       ->orderBy('sort_order', 'asc')
                       ->select('id', 'name', 'slug')
                       ->get();

        return response()->json([
            'status' => true,
            'data' => $topics
        ]);
    }
}
