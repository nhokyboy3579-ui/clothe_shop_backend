<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category; // Giữ lại import Category nếu cần trong các hàm khác
use Illuminate\Http\Request;

class UserBannerController extends Controller
{
    /**
     * Lấy TẤT CẢ Banner đang hoạt động (có thể dùng cho Slideshow hoặc Ads)
     * Endpoint: GET /banners
     */
    public function getSlideshowBanner()
    {
        try {
            $banners = Banner::where('status', 1)
                            ->where('position', 'slideshow')
                            ->orderBy('sort_order', 'asc')
                            ->get(); // FIX: Lấy TẤT CẢ banner Slideshow

            // Trả về mảng các đối tượng Banner
            return response()->json($banners);
        } catch (\Exception $e) {
            \Log::error("Public Slideshow Banner Load Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi tải banner slideshow.'], 500);
        }
    }
}
