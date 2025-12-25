<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Tổng doanh thu (Tính tổng cột total_amount của các đơn đã hoàn thành - status = 3)
        $revenue = Order::where('status', 3)->sum('total_amount');

        // 2. Số đơn hàng mới (status = 1)
        $newOrders = Order::where('status', 1)->count();

        // 3. Tổng số sản phẩm
        $totalProducts = Product::count();

        // 4. Tổng số khách hàng
        $totalUsers = User::where('role', 'customer')->count();

        return response()->json([
            'revenue' => $revenue,
            'new_orders' => $newOrders,
            'total_products' => $totalProducts,
            'total_users' => $totalUsers
        ]);
    }
}
