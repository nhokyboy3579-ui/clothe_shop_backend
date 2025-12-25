<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Import Model Order
use App\Models\OrderDetail; // Import Model OrderDetail
use App\Models\Product; // Cần lấy thông tin Sản phẩm
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // Định nghĩa các trạng thái cho Dropdown (cần thiết cho logic update)
    const STATUS_OPTIONS = [
        1 => 'Mới / Chờ xác nhận',
        2 => 'Đang xử lý',
        3 => 'Đang giao hàng',
        4 => 'Hoàn thành',
        5 => 'Đã hủy',
    ];

    /**
     * Lấy danh sách đơn hàng (index) - FIX: Đã thêm hàm bị thiếu
     */
    public function index()
    {
        try {
            $orders = Order::with('user')
                           ->orderBy('created_at', 'desc')
                           ->get();

            // Map dữ liệu để expose Accessor statusName
            $data = $orders->map(function($order) {
                // Tên người dùng an toàn
                $userInfo = optional($order->user)->name ?? 'Khách vãng lai';

                return [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'user_info' => $userInfo,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'status_code' => $order->status,
                    // Lấy tên trạng thái từ Accessor Model (hoặc hằng số)
                    'status_name' => $order->status_name,
                    'created_at' => optional($order->created_at)->format('Y-m-d H:i')
                ];
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Order Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải danh sách đơn hàng.'], 500);
        }
    }

    /**
     * Xem chi tiết đơn hàng (show)
     */
    public function show($id)
    {
        try {
            // Tải chi tiết đơn hàng và sản phẩm gốc
            $order = Order::with(['user', 'details.product'])
                          ->findOrFail($id);

            // Đảm bảo Accessor trạng thái chạy
            $order->status_name = $order->status_name;

            return response()->json($order);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        } catch (\Exception $e) {
            \Log::error("Order Show Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải chi tiết đơn hàng.'], 500);
        }
    }

    /**
     * Thêm mới đơn hàng từ Admin (STORE)
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:255',
            'payment_method' => 'required|string|max:50',
            'shipping_fee' => 'required|numeric|min:0',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->only([
                'customer_name', 'customer_email', 'customer_phone',
                'shipping_address', 'note', 'payment_method',
                'shipping_fee'
            ]);

            $data['status'] = Order::STATUS_NEW; // Mặc định là trạng thái mới (1)
            $data['payment_status'] = 'Unpaid';
            $data['user_id'] = $request->user() ? $request->user()->id : null; // Gán ID Admin tạo

            $subtotal = 0;
            $details = $request->details;

            // 2. Tính toán tổng tiền hàng
            foreach ($details as $item) {
                $price = (float)$item['price'];
                $quantity = (int)$item['quantity'];
                $subtotal += $price * $quantity;
            }

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal + (float)$data['shipping_fee'];

            // 3. Tạo Order
            $order = Order::create($data);

            // 4. Tạo Order Details
            $orderDetailsData = [];
            foreach ($details as $item) {
                $product = Product::find($item['product_id']);

                $orderDetailsData[] = new OrderDetail([
                    'product_id' => $item['product_id'],
                    'product_name' => $product ? $product->name : 'Sản phẩm không rõ',
                    'variant' => $item['variant'] ?? null,
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price'],
                    'total' => (float)$item['price'] * (int)$item['quantity'],
                ]);
            }

            $order->details()->saveMany($orderDetailsData);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Tạo đơn hàng thành công!',
                'order_id' => $order->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi tạo đơn hàng.'], 500);
        }
    }


    /**
     * Cập nhật trạng thái đơn hàng (PUT /admin/orders/update/{id})
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($id);

        DB::beginTransaction();
        try {
            $order->status = $request->status;
            $order->save();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Cập nhật trạng thái đơn hàng thành công!",
                'status_name' => $order->status_name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order Update Status Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi cập nhật trạng thái.'], 500);
        }
    }

    /**
     * Xóa mềm đơn hàng (DELETE /admin/orders/{id})
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        try {
            $order->delete(); // Soft Delete

            return response()->json([
                'status' => true,
                'message' => 'Đơn hàng đã được xóa mềm.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Order Delete Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi xóa đơn hàng.'], 500);
        }
    }
}
