<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;

class OrderController extends Controller
{
    // 1. Danh sách đơn hàng
    public function index()
    {
        // Lấy danh sách đơn hàng, mới nhất lên đầu
        $orders = Order::with('user')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.order.index', compact('orders'));
    }

    // 2. Chi tiết đơn hàng
    public function show($id)
    {
        // Lấy đơn hàng kèm theo chi tiết và thông tin sản phẩm
        $order = Order::with('details.product')->findOrFail($id);
        return view('admin.order.show', compact('order'));
    }

    // 3. Cập nhật trạng thái đơn hàng
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->updated_by = auth()->id();
        $order->save();

        return redirect()->back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
    }

    // (Tùy chọn) Xóa đơn hàng - Thường thì đơn hàng ít khi xóa hẳn mà chỉ chuyển trạng thái Hủy
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            return redirect()->route('order.index')->with('success', 'Đã xóa đơn hàng!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
