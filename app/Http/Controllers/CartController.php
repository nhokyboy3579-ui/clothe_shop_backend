<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // 1. Xem giỏ hàng
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('frontend.cart', compact('cart'));
    }

    // 2. Thêm vào giỏ (Xử lý POST từ trang chi tiết)
    public function add(Request $request)
    {
        // Kiểm tra đăng nhập
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Vui lòng đăng nhập để mua hàng!');
        }

        $product_id = $request->input('product_id');
        $qty = $request->input('qty', 1);
        $attrs = $request->input('attributes', []); // Mảng thuộc tính user chọn

        $product = Product::findOrFail($product_id);

        // Validate: Kiểm tra xem sản phẩm có thuộc tính ko?
        $requiredAttributes = \App\Models\ProductAttribute::where('product_id', $product_id)
            ->with('attribute')
            ->get()
            ->pluck('attribute.name')
            ->unique();

        if ($requiredAttributes->count() > 0) {
            foreach ($requiredAttributes as $reqAttr) {
                if (!isset($attrs[$reqAttr])) {
                    return back()->with('error', "Vui lòng chọn $reqAttr cho sản phẩm!");
                }
            }
        }

        // Tạo Key độc nhất: ID_MãHóaThuộcTính
        $cartKey = $product_id;
        $attrString = "";
        if (!empty($attrs)) {
            foreach ($attrs as $key => $value) {
                $attrString .= " | $key: $value";
            }
            $cartKey .= '_' . md5(json_encode($attrs));
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['qty'] += $qty;
        } else {
            $cart[$cartKey] = [
                "product_id" => $product->id, // QUAN TRỌNG: Lưu ID gốc để lúc thanh toán dùng
                "name" => $product->name,
                "qty" => $qty,
                "price" => $product->sale ? $product->sale->price_sale : $product->price_buy,
                "image" => $product->thumbnail_url,
                "attributes" => $attrString
            ];
        }

        session()->put('cart', $cart);
        return redirect()->route('site.cart.index')->with('success', 'Đã thêm vào giỏ hàng!');
    }

    // 3. Xóa sản phẩm khỏi giỏ
    public function remove($id)
    {
        $cart = session()->get('cart');
        if(isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        return redirect()->back()->with('success', 'Đã xóa sản phẩm!');
    }

    // 4. Cập nhật số lượng (Nếu cần Ajax sau này)
    public function update(Request $request)
    {
        if($request->id && $request->qty){
            $cart = session()->get('cart');
            $cart[$request->id]["qty"] = $request->qty;
            session()->put('cart', $cart);
            return response()->json(['success' => true]);
        }
    }

    // 5. Trang Thanh toán (Checkout)
    public function checkout()
    {
        $cart = session()->get('cart', []);
        if (count($cart) == 0) {
            return redirect()->route('site.cart.index')->with('error', 'Giỏ hàng trống!');
        }

        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Vui lòng đăng nhập để thanh toán!');
        }

        return view('frontend.checkout', compact('cart'));
    }

    // 6. Xử lý Đặt hàng (Lưu vào DB)
    public function processOrder(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ]);

        $cart = session()->get('cart', []);

        if(empty($cart)) {
            return redirect()->route('site.home')->with('error', 'Giỏ hàng trống!');
        }

        $totalAmount = 0;
        foreach($cart as $item) {
            $totalAmount += $item['price'] * $item['qty'];
        }

        DB::beginTransaction();
        try {
            // 1. Tạo đơn hàng
            $order = Order::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'note' => $request->note,
                'total_amount' => $totalAmount,
                'status' => 1, // 1: Mới đặt
                'created_by' => Auth::id()
            ]);

            // 2. Tạo chi tiết đơn hàng
            foreach($cart as $key => $details) {
                OrderDetail::create([
                    'order_id' => $order->id,

                    // --- SỬA LỖI Ở ĐÂY: Lấy ID thật từ mảng, không lấy $key ---
                    'product_id' => $details['product_id'],

                    // Lưu thuộc tính (Màu, Size)
                    'info' => $details['attributes'] ?? '',

                    'price' => $details['price'],
                    'qty' => $details['qty'],
                    'amount' => $details['price'] * $details['qty']
                ]);
            }

            // 3. Xóa giỏ hàng
            session()->forget('cart');

            DB::commit();
            return redirect()->route('site.home')->with('success', 'Đặt hàng thành công! Mã đơn: #' . $order->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi đặt hàng: ' . $e->getMessage());
        }
    }
}
