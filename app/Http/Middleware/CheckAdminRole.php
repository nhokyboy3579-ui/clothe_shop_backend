<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra đã đăng nhập chưa
        if (Auth::check()) {

            // 2. Kiểm tra quyền: Nếu là 'admin' thì cho qua
            if (Auth::user()->role === 'admin') {
                return $next($request);
            }
        }

        // 3. Nếu không phải Admin (là Customer hoặc chưa đăng nhập):
        // Hủy session, đăng xuất và đá về trang login kèm thông báo lỗi
        Auth::logout();
        return redirect()->route('admin.login')->withErrors([
            'username' => 'Bạn không có quyền truy cập vào trang quản trị!',
        ]);
    }
}
