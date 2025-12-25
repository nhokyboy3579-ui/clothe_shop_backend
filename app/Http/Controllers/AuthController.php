<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // =========================================================
    // PHẦN 1: QUẢN TRỊ VIÊN (BACKEND - ADMIN)
    // =========================================================

    // 1. Form Đăng nhập Admin
    public function showLogin()
    {
        // Nếu đã đăng nhập và là admin thì vào thẳng dashboard
        if (Auth::check() && Auth::user()->role == 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    // 2. Xử lý Đăng nhập Admin
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Xác định đăng nhập bằng email hay username
        $loginType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Điều kiện: Phải đúng tk/mk, trạng thái hoạt động (1), và vai trò là 'admin'
        $credentials = [
            $loginType => $request->username,
            'password' => $request->password,
            'status' => 1,
            'role' => 'admin'
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard')->with('success', 'Xin chào Quản trị viên!');
        }

        return back()->withErrors([
            'username' => 'Tài khoản không đúng hoặc bạn không có quyền truy cập.',
        ])->onlyInput('username');
    }

    // 3. Trang Dashboard Admin
    public function dashboard()
    {
        $user = Auth::user();
        return view('admin.dashboard', compact('user'));
    }

    // 4. Đăng xuất Admin
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }


    // =========================================================
    // PHẦN 2: KHÁCH HÀNG (FRONTEND - SITE)
    // =========================================================

    // 1. Form Đăng nhập Khách
    public function showSiteLogin()
    {
        if (Auth::check()) {
            return redirect()->route('site.home');
        }
        return view('frontend.login');
    }

    // 2. Xử lý Đăng nhập Khách
    public function siteLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $loginType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginType => $request->username,
            'password' => $request->password,
            'status' => 1
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // --- QUAN TRỌNG: redirect()->intended(...) ---
            // Nó sẽ đưa người dùng về trang họ định vào trước đó (ví dụ: Checkout)
            // Nếu không có lịch sử, nó sẽ về trang chủ (site.home)
            return redirect()->intended(route('site.home'))->with('success', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'username' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('username');
    }

    // 3. Form Đăng ký Khách
    public function showSiteRegister()
    {
        if (Auth::check()) {
            return redirect()->route('site.home');
        }
        return view('frontend.register');
    }

    // 4. Xử lý Đăng ký Khách
    public function siteRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2',
            'username' => 'required|unique:users,username|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed', // password_confirmation
            'phone' => 'required|numeric'
        ], [
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        try {
            // Tạo tài khoản Customer
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'customer', // Mặc định là khách hàng
                'status' => 1,
                'created_by' => 1 // Có thể để NULL hoặc ID admin hệ thống
            ]);

            // Đăng nhập luôn sau khi đăng ký thành công
            Auth::login($user);

            // Chuyển về trang chủ
            return redirect()->route('site.home')->with('success', 'Đăng ký thành công! Chào mừng bạn gia nhập.');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage())->withInput();
        }
    }

    // 5. Đăng xuất Khách (Redirect về trang chủ)
    public function siteLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('site.home');
    }
}
