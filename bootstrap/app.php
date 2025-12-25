<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // Nhớ thêm dòng này

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // CẤU HÌNH ĐIỀU HƯỚNG THÔNG MINH
        $middleware->redirectGuestsTo(function (Request $request) {

            // Nếu đường dẫn bắt đầu bằng 'admin' (VD: admin/dashboard, admin/product...)
            if ($request->is('admin/*')) {
                return route('admin.login');
            }

            // Các trường hợp còn lại (Trang chủ, Giỏ hàng, Profile...) -> Về Login Khách
            return route('site.login');
        });

        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdminRole::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
