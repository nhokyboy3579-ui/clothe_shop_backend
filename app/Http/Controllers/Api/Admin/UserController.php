<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();

        // Map thêm full url cho avatar
        $data = $users->map(function($user) {
            $user->full_avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
            return $user;
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,customer',
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Validate ảnh
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $avatarPath = null;
            // Xử lý upload ảnh
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status ?? 1,
                'avatar' => $avatarPath
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tạo tài khoản thành công',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Không tìm thấy user'], 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => ['required', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,customer',
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->role = $request->role;
            $user->status = $request->status;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Xử lý update ảnh
            if ($request->hasFile('avatar')) {
                // Xóa ảnh cũ nếu có
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                // Lưu ảnh mới
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thành công',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            // Xóa ảnh trong storage khi xóa user
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->delete();
            return response()->json(['status' => true, 'message' => 'Xóa thành công']);
        }
        return response()->json(['message' => 'Không tìm thấy user'], 404);
    }
}
