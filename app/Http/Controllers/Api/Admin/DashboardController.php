<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Thống kê tổng quát (Dòng tiền)
        $moneyReceived = Order::where('status', 4)->sum('total_amount'); // Giả sử 4 là Hoàn thành
        $moneyPending = Order::whereIn('status', [1, 2, 3])->sum('total_amount'); // Đang xử lý/giao

        // 2. Doanh thu 7 ngày gần nhất (Dùng cho biểu đồ đường)
        $dailyRevenue = Order::where('status', 4)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // 3. Doanh thu theo tháng trong năm hiện tại
        $monthlyRevenue = Order::where('status', 4)
            ->whereYear('created_at', date('Y'))
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();

        // 4. 5 đơn hàng mới nhất
        $latestOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'customer' => $order->customer_name,
                    'total' => $order->total_amount,
                    'status' => $order->status, // Cần khớp với hằng số trạng thái
                    'created_at' => $order->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'stats' => [
                'money_received' => $moneyReceived,
                'money_pending' => $moneyPending,
                'total_products' => Product::count(),
                'total_users' => User::where('role', 'customer')->count(),
            ],
            'charts' => [
                'daily' => $dailyRevenue,
                'monthly' => $monthlyRevenue
            ],
            'latest_orders' => $latestOrders
        ]);
    }
}
