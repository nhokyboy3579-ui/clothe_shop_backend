<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\{Hash, Mail, Cache, Validator};

class ForgotPasswordController extends Controller
{
    /**
     * Gửi mã OTP khôi phục mật khẩu
     */
    public function sendOtp(Request $request)
    {
        // 1. Validate email gửi từ Frontend
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.exists'   => 'Email này chưa được đăng ký trên hệ thống.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 2. TRUY VẤN DATABASE: Lấy thông tin User qua email từ frontend
            $user = User::where('email', $request->email)->first();

            // Lấy Họ tên và Tên đăng nhập
            $name = $user->name;
            $username = $user->username;

            // 3. Tạo mã OTP và lưu vào Cache 60 giây
            $otp = rand(100000, 999999);
            Cache::put('otp_' . $request->email, $otp, 60);

            // 4. Gửi Mail cá nhân hóa với đầy đủ Name và Username
            $mailContent = "Xin chào {$name},\n\n"
                         . "Hệ thống nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn.\n"
                         . "Thông tin tài khoản:\n"
                         . "- Tên đăng nhập: {$username}\n"
                         . "- Mã xác thực (OTP): {$otp}\n\n"
                         . "Lưu ý: Mã này chỉ có hiệu lực trong vòng 60 giây. "
                         . "Nếu không phải bạn yêu cầu, hãy bỏ qua email này hoặc liên hệ hỗ trợ.";

            Mail::raw($mailContent, function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Xác thực khôi phục mật khẩu - ' . config('app.name'));
            });

            return response()->json([
                'status' => true,
                'message' => 'Mã xác thực đã được gửi đến email của bạn!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Lỗi gửi mail OTP: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Lỗi hệ thống khi gửi mail. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Xác thực OTP và đặt lại mật khẩu mới
     */
    public function verifyAndReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'password' => 'required|min:6|confirmed',
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
            'password.min' => 'Mật khẩu mới phải từ 6 ký tự.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cachedOtp = Cache::get('otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Mã OTP không chính xác hoặc đã hết hạn.'
            ], 400);
        }

        // Cập nhật mật khẩu mới cho đúng user
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Xóa OTP khỏi cache
        Cache::forget('otp_' . $request->email);

        return response()->json([
            'status' => true,
            'message' => 'Mật khẩu của bạn đã được cập nhật thành công!'
        ]);
    }
}
