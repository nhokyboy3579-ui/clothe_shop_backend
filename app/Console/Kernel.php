<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Order;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Định nghĩa các lệnh lập lịch cho ứng dụng.
     */
    protected function schedule(Schedule $schedule): void
    {
        // TỰ ĐỘNG HỦY ĐƠN HÀNG QUÁ HẠN THANH TOÁN
        // Chạy mỗi 5 phút một lần
        $schedule->call(function () {
            $expiryTime = Carbon::now()->subMinutes(15);

            // Tìm các đơn hàng:
            // 1. Trạng thái là "Mới" (1)
            // 2. Chưa thanh toán (payment_status khác 'Paid')
            // 3. Đã tạo quá 15 phút trước
            Order::where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('payment_status')
                        ->orWhere('payment_status', '!=', 'Paid');
                })
                ->where('created_at', '<=', $expiryTime)
                ->update([
                    'status' => 5, // Trạng thái Hủy
                    'cancel_reason' => 'Hệ thống tự động hủy do quá hạn thanh toán (15 phút).'
                ]);
        })->everyFiveMinutes();
    }

    /**
     * Đăng ký các lệnh (commands) cho ứng dụng.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
