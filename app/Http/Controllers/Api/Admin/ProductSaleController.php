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
     * Lấy danh sách Sale (kèm tìm kiếm theo tên SP và phân trang)
     */
    public function index(Request $request)
    {
        try {
            $query = ProductSale::with('product')->orderBy('id', 'desc');

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                // Tìm kiếm dựa trên tên sản phẩm trong bảng quan hệ
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $limit = $request->limit ?? 10;
            $sales = $query->paginate($limit);

            return response()->json($sales);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi tải danh sách giảm giá: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Thêm mới Sale
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
            $data['created_by'] = $request->user() ? $request->user()->id : null;

            $sale = ProductSale::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Thêm chương trình giảm giá thành công!',
                'sale' => $sale
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server khi thêm sale.'], 500);
        }
    }

    /**
     * Cập nhật Sale
     */
    public function update(Request $request, $id)
    {
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

        try {
            $sale->update($request->all());
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thành công!',
                'sale' => $sale
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server khi cập nhật sale.'], 500);
        }
    }

    /**
     * Xóa Sale
     */
    public function destroy($id)
    {
        try {
            $sale = ProductSale::findOrFail($id);
            $sale->delete();
            return response()->json(['status' => true, 'message' => 'Xóa thành công!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi xóa sale.'], 500);
        }
    }
}
