<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with('user')->orderBy('id', 'desc')->get();
            $data = $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'user_info' => optional($order->user)->name ?? 'Khách vãng lai',
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'status_code' => $order->status,
                    'status_name' => $order->status_name,
                    'created_at' => optional($order->created_at)->format('Y-m-d H:i')
                ];
            });
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi tải danh sách.'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:255',
            'payment_method' => 'required|string',
            'shipping_fee' => 'required|numeric',
            'details' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Tính toán lại tiền từ Server để đảm bảo an toàn
            $subtotal = 0;
            foreach ($request->details as $item) {
                $subtotal += (float)$item['price'] * (int)$item['quantity'];
            }

            // Tạo Order
            $order = Order::create([
                'customer_name'    => $request->customer_name,
                'customer_email'   => $request->customer_email,
                'customer_phone'   => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'note'             => $request->note,
                'payment_method'   => $request->payment_method,
                'shipping_fee'     => (float)$request->shipping_fee,
                'subtotal'         => $subtotal,
                'total_amount'     => $subtotal + (float)$request->shipping_fee,
                'status'           => 1, // Mặc định: Mới
                'payment_status'   => 'Unpaid',
                'user_id'          => auth()->id(), // Lấy ID admin đang login
            ]);

            // Tạo Order Details
            foreach ($request->details as $item) {
                $product = Product::findOrFail($item['product_id']);
                OrderDetail::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'variant'      => isset($item['variant']) ? json_encode($item['variant']) : null,
                    'quantity'     => (int)$item['quantity'],
                    'price'        => (float)$item['price'],
                    'total'        => (float)$item['price'] * (int)$item['quantity'],
                ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Tạo đơn hàng thành công!'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi Server: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with(['user', 'details.product'])->findOrFail($id);
        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);
        return response()->json(['status' => true, 'message' => 'Cập nhật thành công!']);
    }

    public function destroy($id)
    {
        Order::findOrFail($id)->delete();
        return response()->json(['status' => true, 'message' => 'Đã xóa đơn hàng.']);
    }
}
