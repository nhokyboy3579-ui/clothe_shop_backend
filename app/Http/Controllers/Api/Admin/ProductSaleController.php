<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductSale;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProductSaleController extends Controller
{
    /**
     * Lấy danh sách Sale (Kèm tìm kiếm, lọc trạng thái và sắp xếp)
     * URL: GET /api/admin/product-sales?search=...&status_filter=...&sort_price=...
     */
    public function index(Request $request)
    {
        try {
            // Khởi tạo query với eager loading để tối ưu hiệu năng
            $query = ProductSale::with('product');

            // 1. Tìm kiếm theo tên sản phẩm (Quan hệ bảng products)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // 2. Lọc theo trạng thái logic dựa trên thời gian thực
            $now = Carbon::now();
            if ($request->filled('status_filter')) {
                switch ($request->status_filter) {
                    case 'active': // Đang chạy: status=1 và nằm trong khoảng thời gian
                        $query->where('status', 1)
                            ->where('date_begin', '<=', $now)
                            ->where('date_end', '>=', $now);
                        break;
                    case 'upcoming': // Sắp bắt đầu: status=1 và chưa tới ngày
                        $query->where('status', 1)
                            ->where('date_begin', '>', $now);
                        break;
                    case 'expired': // Hết hạn: status=1 và đã qua ngày kết thúc
                        $query->where('status', 1)
                            ->where('date_end', '<', $now);
                        break;
                    case 'hidden': // Tạm ẩn: status=0
                        $query->where('status', 0);
                        break;
                }
            }

            // 3. Sắp xếp theo giá giảm hoặc mặc định theo ID mới nhất
            if ($request->filled('sort_price')) {
                // sort_price: 'asc' (tăng dần) hoặc 'desc' (giảm dần)
                $query->orderBy('price_sale', $request->sort_price);
            } else {
                $query->orderBy('id', 'desc');
            }

            // 4. Phân trang
            $limit = $request->limit ?? 10;
            $sales = $query->paginate($limit);

            return response()->json($sales);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi tải danh sách giảm giá: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm mới chương trình giảm giá
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'price_sale' => 'required|numeric|min:0',
            'date_begin' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_begin',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            // Lưu ID người tạo nếu hệ thống có Auth
            $data['created_by'] = $request->user() ? $request->user()->id : null;

            $sale = ProductSale::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Thêm chương trình giảm giá thành công!',
                'sale' => $sale
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server khi thêm sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật chương trình giảm giá
     */
    public function update(Request $request, $id)
    {
        try {
            $sale = ProductSale::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'price_sale' => 'required|numeric|min:0',
                'date_begin' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_begin',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $sale->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thành công!',
                'sale' => $sale
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server khi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa chương trình giảm giá
     */
    public function destroy($id)
    {
        try {
            $sale = ProductSale::findOrFail($id);
            $sale->delete();
            return response()->json([
                'status' => true,
                'message' => 'Xóa chương trình giảm giá thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server khi xóa sale: ' . $e->getMessage()
            ], 500);
        }
    }
}
