<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class RelatedProductController extends Controller
{
    /**
     * Lấy sản phẩm liên quan theo category_id
     * URL: /api/related-products/{id}
     */
    public function getRelated($id)
    {
        try {
            // 1. Tìm sản phẩm hiện tại để lấy category_id
            $currentProduct = Product::select('id', 'category_id')->findOrFail($id);

            // 2. Lấy danh sách sản phẩm cùng danh mục (loại trừ chính nó)
            $related = Product::where('category_id', $currentProduct->category_id)
                ->where('id', '!=', $id)
                ->where('status', 0) // Chỉ lấy hàng đang bán
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();

            // 3. Map dữ liệu chuẩn format frontend của bạn
            $data = $related->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price_buy,
                    'category_name' => optional($product->category)->name,
                    'image' => $product->thumbnail_url,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể lấy sản phẩm liên quan'
            ], 500);
        }
    }
}
