<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SiteController extends Controller
{
    // 1. Trang chủ
    public function index()
    {
        $newProducts = Product::where('status', 1)
                        ->with(['sale', 'category'])
                        ->orderBy('created_at', 'desc')
                        ->take(8)
                        ->get();

        return view('frontend.home', compact('newProducts'));
    }

    // 2. Trang hồ sơ cá nhân
    public function profile()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Vui lòng đăng nhập.');
        }
        return view('frontend.profile');
    }

    // 3. Cập nhật hồ sơ
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|min:6|confirmed',
        ]);

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;

            if ($request->hasFile('avatar')) {
                if ($user->avatar && file_exists(public_path('images/user/' . $user->avatar))) {
                    unlink(public_path('images/user/' . $user->avatar));
                }
                $file = $request->file('avatar');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/user'), $filename);
                $user->avatar = $filename;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            return back()->with('success', 'Cập nhật hồ sơ thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    }

    // 4. Chi tiết sản phẩm
    public function productDetail($slug)
    {
        $product = Product::where('slug', $slug)
                        ->where('status', 1)
                        ->with(['category', 'images', 'sale'])
                        ->firstOrFail();

        $productAttributes = ProductAttribute::where('product_id', $product->id)
                            ->with('attribute')
                            ->get()
                            ->groupBy(function($item) {
                                return $item->attribute->name;
                            });

        $relatedProducts = Product::where('category_id', $product->category_id)
                            ->where('id', '!=', $product->id)
                            ->where('status', 1)
                            ->take(4)
                            ->get();

        return view('frontend.product_detail', compact('product', 'productAttributes', 'relatedProducts'));
    }

    // 5. Danh sách đơn hàng của tôi
    public function myOrders()
    {
        $orders = Order::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('frontend.my_orders', compact('orders'));
    }

    // 6. Chi tiết đơn hàng
    public function myOrderDetail($id)
    {
        $order = Order::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->with('details.product')
                    ->firstOrFail();

        return view('frontend.my_order_detail', compact('order'));
    }

    // 7. Hủy đơn hàng (MỚI THÊM)
    public function cancelOrder($id)
    {
        $order = Order::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$order) {
            return back()->with('error', 'Đơn hàng không tồn tại!');
        }

        if ($order->status != 1) {
            return back()->with('error', 'Đơn hàng đã được xử lý, không thể hủy lúc này!');
        }

        $order->status = 5; // 5: Đã hủy
        $order->save();

        return back()->with('success', 'Đã hủy đơn hàng thành công!');
    }
}
