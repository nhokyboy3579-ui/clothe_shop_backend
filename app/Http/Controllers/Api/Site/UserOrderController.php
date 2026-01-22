<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserOrderController extends Controller
{
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'customer_email'   => 'nullable|email|max:255',
            'payment_method'   => 'required|in:COD,BANKING',
            'cart_items'       => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity'   => 'required|integer|min:1',
            'cart_items.*.price'      => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = auth('sanctum')->user();
            $subtotal = 0;
            $orderDetailsData = [];

            $productIds = collect($request->cart_items)->pluck('product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($request->cart_items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    throw new \Exception("Sản phẩm ID " . $item['product_id'] . " không tồn tại.");
                }

                $price = $product->price ?? $item['price'];
                $price = (float) $price;
                $quantity = (int) $item['quantity'];
                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;

                $orderDetailsData[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'variant'      => isset($item['variant']) ? (is_array($item['variant']) ? json_encode($item['variant']) : $item['variant']) : null,
                    'quantity'     => $quantity,
                    'price'        => $price,
                    'total'        => $lineTotal,
                ];
            }

            $shippingFee = $request->shipping_fee ?? 0;

            $order = Order::create([
                'user_id'          => $user ? $user->id : null,
                'customer_name'    => $request->customer_name,
                'customer_email'   => $request->customer_email,
                'customer_phone'   => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'note'             => $request->note,
                'payment_method'   => $request->payment_method,
                'payment_status'   => 'Unpaid',
                'subtotal'         => $subtotal,
                'shipping_fee'     => $shippingFee,
                'total_amount'     => $subtotal + $shippingFee,
                'status'           => Order::STATUS_NEW,
            ]);

            $order->details()->createMany($orderDetailsData);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Lỗi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function myOrders()
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $orders = Order::with(['details.product'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id'               => $order->id,
                        'status'           => $order->status,
                        'customer_name'    => $order->customer_name,
                        'customer_phone'   => $order->customer_phone,
                        'shipping_address' => $order->shipping_address,
                        'total_amount'     => (float) $order->total_amount,
                        'created_at'       => $order->created_at,
                        'details'          => $order->details->map(function ($detail) {
                            // Lấy giá trị từ cột thumbnail
                            $imgSource = $detail->product->thumbnail ?? null;

                            // Kiểm tra nếu imgSource đã có sẵn http (link ngoài) thì giữ nguyên,
                            // ngược lại thì nối với domain qua asset()
                            $imageUrl = null;
                            if ($imgSource) {
                                $imageUrl = str_contains($imgSource, 'http')
                                    ? $imgSource
                                    : asset('storage/' . $imgSource);
                            }

                            return [
                                'product_name'  => $detail->product_name,
                                'quantity'      => (int) $detail->quantity,
                                'price'         => (float) $detail->price,
                                'variant'       => $detail->variant,
                                'product_image' => $imageUrl ?? asset('images/default-product.png'),
                            ];
                        })
                    ];
                });

            return response()->json(['status' => true, 'data' => $orders], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function cancelOrder($id)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này'], 401);
            }

            // Tìm đơn hàng thuộc về user này
            $order = Order::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
            }

            /**
             * Kiểm tra trạng thái:
             * Giả định code trạng thái của bạn là số: 1 (Mới), 2 (Xử lý)
             * Nếu Database lưu dạng chuỗi thì bạn thay bằng Order::STATUS_NEW hoặc giá trị tương ứng.
             */
            $allowedStatuses = [1, 2]; // Chỉ cho phép hủy khi ở trạng thái 1 hoặc 2

            // Nếu Model của bạn dùng hằng số hoặc text, hãy map giá trị status về số để check
            if (!in_array($order->status, $allowedStatuses)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Đơn hàng hiện không thể hủy do đang trong quá trình vận chuyển hoặc đã hoàn thành.'
                ], 400);
            }

            // Cập nhật trạng thái về 5 (Đã hủy)
            $order->update([
                'status' => 5
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Đơn hàng #' . $id . ' đã được hủy thành công.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }
}
