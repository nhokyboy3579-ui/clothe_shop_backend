<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Thêm Log để debug nếu cần

class ProductSaleController extends Controller
{
    // Lấy danh sách Sale
    public function index()
    {
        // Lấy dữ liệu mới nhất lên đầu (orderBy desc)
        $sales = ProductSale::with('product')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($sales);
    }

    // Tạo mới Sale
    public function store(Request $request)
    {
        try {
            // 1. Validate dữ liệu
            $request->validate([
                'product_id' => 'required|exists:products,id', // Sản phẩm phải tồn tại
                'price_sale' => 'required|numeric|min:0',      // Giá giảm >= 0
                'date_begin' => 'required|date',               // Phải là ngày tháng
                'date_end'   => 'required|date|after:date_begin', // Ngày kết thúc phải sau ngày bắt đầu
            ], [
                'product_id.exists' => 'Sản phẩm không tồn tại.',
                'date_end.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            ]);

            // 2. Kiểm tra trùng lặp (Optional): Nếu SP này đang chạy sale thì không thêm nữa
            $exists = ProductSale::where('product_id', $request->product_id)
                ->where('date_end', '>', now()) // Vẫn còn hạn
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Sản phẩm này đang có chương trình khuyến mãi chạy.'], 400);
            }

            // 3. Tạo record
            $sale = ProductSale::create([
                'product_id' => $request->product_id,
                'price_sale' => $request->price_sale,
                'date_begin' => $request->date_begin,
                'date_end'   => $request->date_end,
                'status'     => 1,
                // Nếu chưa có login, để mặc định 'Admin', nếu có thì lấy tên User
                'created_by' => $request->user() ? $request->user()->name : 'Admin',
            ]);

            return response()->json([
                'message' => 'Tạo khuyến mãi thành công',
                'data' => $sale
            ], 201);

        } catch (\Exception $e) {
            // Ghi log lỗi để kiểm tra trong storage/logs/laravel.log
            Log::error("Lỗi tạo sale: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi Server: ' . $e->getMessage()], 500);
        }
    }

    // Xóa Sale
    public function destroy($id)
    {
        $sale = ProductSale::findOrFail($id);
        $sale->delete();
        return response()->json(['message' => 'Đã xóa khuyến mãi']);
    }
}
