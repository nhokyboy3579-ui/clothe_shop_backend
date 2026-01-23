<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductStore;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductStoreController extends Controller
{
    /**
     * 1. Lấy danh sách kho hàng (Có phân trang)
     */
    public function index(Request $request)
    {
        try {
            $query = ProductStore::with(['product', 'creator']);

            // 1. Tìm kiếm theo tên sản phẩm (thông qua bảng quan hệ)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // 2. Lọc theo trạng thái (0: Ẩn, 1: Hiện)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 3. Sắp xếp theo giá gốc (price_root) hoặc số lượng (qty)
            if ($request->filled('sort_price')) {
                $query->orderBy('price_root', $request->sort_price); // 'asc' hoặc 'desc'
            } elseif ($request->filled('sort_qty')) {
                $query->orderBy('qty', $request->sort_qty);
            } else {
                // Mặc định sắp xếp theo ID mới nhất
                $query->orderBy('id', 'desc');
            }

            $limit = $request->limit ?? 10;
            $items = $query->paginate($limit);

            return response()->json([
                'status' => true,
                'message' => 'Tải danh sách kho thành công',
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Thêm mới (Nhập kho)
     */
    public function store(Request $request)
    {
        // Validate dữ liệu
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'price_root' => 'required|numeric|min:0', // Giá nhập không được âm
            'qty'        => 'required|integer|min:1',  // Số lượng phải > 0
            'status'     => 'nullable|integer',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.exists'   => 'Sản phẩm không tồn tại',
            'price_root.required' => 'Vui lòng nhập giá gốc',
            'qty.required'        => 'Vui lòng nhập số lượng',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            // Tự động lấy ID người đang đăng nhập (Admin)
            $data['created_by'] = Auth::id() ?? 1; // Nếu chưa setup Auth thì mặc định là 1 (Admin)

            $store = ProductStore::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Nhập kho thành công!',
                'data' => $store
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Xem chi tiết 1 phiếu nhập
     */
    public function show($id)
    {
        $store = ProductStore::with('product')->find($id);

        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu'], 404);
        }

        return response()->json(['status' => true, 'data' => $store]);
    }

    /**
     * 4. Cập nhật thông tin kho (Sửa giá nhập hoặc số lượng)
     */
    public function update(Request $request, $id)
    {
        $store = ProductStore::find($id);

        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $validator = Validator::make($request->all(), [
            'price_root' => 'numeric|min:0',
            'qty'        => 'integer|min:0',
            'status'     => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $store->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật kho thành công!',
                'data' => $store
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 5. Xóa phiếu nhập kho
     */
    public function destroy($id)
    {
        $store = ProductStore::find($id);

        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu'], 404);
        }

        try {
            $store->delete();
            return response()->json([
                'status' => true,
                'message' => 'Xóa dữ liệu kho thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi khi xóa: ' . $e->getMessage()], 500);
        }
    }
}
