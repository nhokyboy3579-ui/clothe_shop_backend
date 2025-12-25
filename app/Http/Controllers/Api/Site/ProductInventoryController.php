<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ProductStore;
use App\Models\OrderDetail;
use Illuminate\Http\Request;

class ProductInventoryController extends Controller
{
    public function show($productId)
    {
        // 1. Kiểm tra xem có bất kỳ phiếu nhập nào không (kể cả ẩn hay hiện)
        $hasAnyStore = ProductStore::where('product_id', $productId)->exists();

        // 2. Tính tổng nhập của các phiếu ĐANG HOẠT ĐỘNG (status = 1)
        $activeStores = ProductStore::where('product_id', $productId)->where('status', 1);
        $totalImportActive = $activeStores->sum('qty');
        $hasActiveStore = $activeStores->exists();

        // 3. Tính tổng đã bán
        $totalSold = OrderDetail::whereHas('order', function ($query) {
            $query->whereIn('status', [1, 2, 3, 4]);
        })->where('product_id', $productId)->sum('quantity');

        // 4. Tính tồn kho (Chỉ dựa trên các phiếu Active)
        $stock = $totalImportActive - $totalSold;
        $stock = max($stock, 0);

        // 5. XÁC ĐỊNH TRẠNG THÁI
        $statusText = 'Còn hàng';

        if (!$hasAnyStore) {
            // Chưa từng nhập kho lần nào
            $statusText = 'Sắp ra mắt';
        } elseif (!$hasActiveStore) {
            // Đã từng nhập, nhưng hiện tại tất cả phiếu nhập đều bị Ẩn (Status = 0)
            $statusText = 'Ngừng kinh doanh';
            $stock = 0; // Ép tồn kho về 0 để không mua được
        } elseif ($stock <= 0) {
            // Các phiếu Active đã bán hết hàng
            $statusText = 'Hết hàng';
        }

        return response()->json([
            'status' => true,
            'data' => [
                'product_id' => (int)$productId,
                'stock' => (int)$stock,
                'status_text' => $statusText // Trả về text trạng thái để frontend dùng
            ]
        ]);
    }
}
