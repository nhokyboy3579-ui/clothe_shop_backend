<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order; // Nhớ import Model Order
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // ... (Các hàm register, logout giữ nguyên)

    // 1. ĐĂNG KÝ (Giữ nguyên)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công',
            'user' => $user
        ], 201);
    }

    // 2. ĐĂNG NHẬP (Sửa: Tính thêm total_spent)
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'status' => 1
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
            ], 401);
        }

        $user = Auth::user();
        $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        // --- TÍNH TỔNG TIỀN ĐÃ CHI ---
        // Chỉ tính những đơn hàng có status = 3 (Hoàn thành)
        $totalSpent = Order::where('user_id', $user->id)->where('status', 3)->sum('total_amount');
        $user->total_spent = (int)$totalSpent;

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    // 3. ĐĂNG XUẤT (Giữ nguyên)
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['status' => true, 'message' => 'Đăng xuất thành công']);
    }

    // 4. LẤY PROFILE (Sửa: Tính thêm total_spent)
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        // --- TÍNH TỔNG TIỀN ĐÃ CHI ---
        $totalSpent = Order::where('user_id', $user->id)->where('status', 3)->sum('total_amount');
        $user->total_spent = (int)$totalSpent;

        return response()->json($user);
    }

    // 5. CẬP NHẬT PROFILE (Sửa: Trả về total_spent để frontend không bị mất rank khi update)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            $user->save();

            $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

            // Tính lại tiền để trả về cho đồng bộ
            $totalSpent = Order::where('user_id', $user->id)->where('status', 3)->sum('total_amount');
            $user->total_spent = (int)$totalSpent;

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật hồ sơ thành công',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }
}
