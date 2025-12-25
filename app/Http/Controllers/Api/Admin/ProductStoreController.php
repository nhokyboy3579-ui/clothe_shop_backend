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
        $query = ProductStore::with(['product', 'creator'])
            ->orderBy('id', 'desc');

        // Lọc theo sản phẩm nếu cần
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $items = $query->paginate(10); // Lấy 10 dòng mỗi trang

        return response()->json([
            'status' => true,
            'message' => 'Tải danh sách kho thành công',
            'data' => $items
        ]);
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
