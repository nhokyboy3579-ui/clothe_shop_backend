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
            'cart_items.*.price'      => 'required|numeric', // Thêm validate giá từ frontend
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

                /**
                 * XỬ LÝ LỖI NULL PRICE:
                 * Ưu tiên lấy giá từ Database ($product->price).
                 * Nếu DB đang null hoặc bằng 0, lấy giá từ Frontend ($item['price']).
                 */
                $price = $product->price ?? $item['price'];

                // Ép kiểu về float để tính toán chính xác
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
}
