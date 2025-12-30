<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * 1. ĐĂNG KÝ (Đã validate trùng username & email)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|numeric|digits_between:10,11',
        ], [
            'username.required' => 'Tên đăng nhập không được để trống.',
            'username.min' => 'Tên đăng nhập phải có ít nhất 3 ký tự.',
            'username.unique' => 'Tên đăng nhập này đã tồn tại trong hệ thống.',
            'email.required' => 'Địa chỉ email không được để trống.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.numeric' => 'Số điện thoại phải là định dạng số.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
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
                'message' => 'Đăng ký tài khoản thành công!',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. ĐĂNG NHẬP (Validate đầu vào & tính tổng chi tiêu)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
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
                'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác.'
            ], 401);
        }

        $user = Auth::user();
        $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        // Tính tổng tiền đã chi (Status 3 = Hoàn thành)
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

    /**
     * 3. LẤY HỒ SƠ (Profile)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        $totalSpent = Order::where('user_id', $user->id)->where('status', 3)->sum('total_amount');
        $user->total_spent = (int)$totalSpent;

        return response()->json($user);
    }

    /**
     * 4. CẬP NHẬT HỒ SƠ (Validate trùng lập nhưng bỏ qua ID bản thân)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|numeric|digits_between:10,11',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|min:6|confirmed'
        ], [
            'email.unique' => 'Email này đã thuộc về một thành viên khác.',
            'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.'
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
            $totalSpent = Order::where('user_id', $user->id)->where('status', 3)->sum('total_amount');
            $user->total_spent = (int)$totalSpent;

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thông tin thành công',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 5. ĐĂNG XUẤT
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['status' => true, 'message' => 'Đã đăng xuất thành công.']);
    }
}
