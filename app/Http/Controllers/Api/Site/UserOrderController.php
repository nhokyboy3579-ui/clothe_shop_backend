<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product; // Import Model Product
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserOrderController extends Controller
{
    /**
     * Xử lý quá trình thanh toán và tạo đơn hàng.
     * * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        // 1. Validate dữ liệu đầu vào từ Frontend (Chỉ validate dữ liệu thô)
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'customer_email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
            'payment_method' => 'required|string|in:COD,BANKING',

            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.variant' => 'nullable|string', // Variant là JSON string
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = $request->user();
            $subtotal = 0;
            $order_details_data = [];

            // --- 2. BẢO MẬT: TRUY VẤN LẠI SẢN PHẨM TỪ DB ---
            $product_ids = array_column($request->cart_items, 'product_id');
            $products = Product::whereIn('id', $product_ids)->get()->keyBy('id');

            foreach ($request->cart_items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json(['message' => 'Sản phẩm ID ' . $item['product_id'] . ' không hợp lệ.'], 404);
                }

                $quantity = $item['quantity'];
                $price = $product->price; // Lấy giá từ DATABASE
                $total_detail = $price * $quantity;
                $subtotal += $total_detail;

                // Chuẩn bị mảng chi tiết đơn hàng
                $order_details_data[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name, // Snapshot tên sản phẩm
                    'variant' => $item['variant'] ?? null,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total_detail,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3. Tính toán tổng tiền
            $shipping_fee = 0;
            $total_amount = $subtotal + $shipping_fee;

            // --- 4. TẠO ĐƠN HÀNG (ORDERS) ---
            $order = Order::create([
                'user_id' => $user ? $user->id : null,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'note' => $request->note,
                'payment_method' => $request->payment_method ?? 'COD',
                'payment_status' => 'Unpaid',
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping_fee,
                'total_amount' => $total_amount,
                'status' => Order::STATUS_NEW, // 1: Mới
                'created_by' => $user ? $user->id : null,
            ]);

            // --- 5. TẠO CHI TIẾT ĐƠN HÀNG (ORDER_DETAILS) ---
            $order->details()->createMany($order_details_data);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order Checkout Error: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi đặt hàng, vui lòng thử lại sau.'], 500);
        }
    }
}
