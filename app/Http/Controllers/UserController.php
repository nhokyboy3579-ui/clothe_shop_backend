<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // 1. Hiển thị danh sách
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.user.index', compact('users'));
    }

    // 2. Hiển thị form thêm mới
    public function create()
    {
        return view('admin.user.create');
    }

    // 3. Xử lý lưu dữ liệu (QUAN TRỌNG)
    public function store(Request $request)
    {
        // 1. Validate dữ liệu
        $request->validate([
            'name' => 'required|min:2',
            'username' => 'required|unique:users,username', // Bắt buộc duy nhất
            'email' => 'required|email|unique:users,email', // Bắt buộc duy nhất
            'password' => 'required|min:6|confirmed',       // Bắt buộc khớp với password_confirmation
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'name.required' => 'Họ tên không được để trống',
            'username.unique' => 'Tên đăng nhập đã tồn tại, hãy chọn tên khác',
            'email.unique' => 'Email này đã được đăng ký',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên'
        ]);

        try {
            // 2. Khởi tạo đối tượng
            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->role = $request->role; // 'admin' hoặc 'customer'
            $user->status = $request->status; // 1 hoặc 0

            // Mặc định người tạo là ID 1.
            // Lưu ý: Trong Database phải có User ID 1 rồi, nếu chưa có sẽ lỗi.
            // Nếu chưa có ai đăng nhập, ta để tạm NULL hoặc 1.
            $user->created_by = 1;

            // 3. Xử lý upload ảnh
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();

                // Di chuyển ảnh vào public/images/user
                $file->move(public_path('images/user'), $filename);
                $user->avatar = $filename;
            }

            $user->save();

            return redirect()->route('user.index')->with('success', 'Thêm thành viên mới thành công!');
        } catch (\Exception $e) {
            // Nếu có lỗi SQL hoặc lỗi hệ thống, quay lại trang trước và hiện lỗi
            return back()
                ->withInput() // Giữ lại dữ liệu đã nhập
                ->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
    // 4. Hiển thị form chỉnh sửa
    public function edit($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect()->route('user.index')->with('error', 'Thành viên không tồn tại!');
        }
        return view('admin.user.edit', compact('user'));
    }

    // 5. Xử lý cập nhật dữ liệu
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:2',
            // Username: Bắt buộc, Duy nhất nhưng TRỪ ID hiện tại ra
            'username' => 'required|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // Password: Có thể để trống (nullable), nếu nhập thì phải khớp confirmation
            'password' => 'nullable|min:6|confirmed',
        ], [
            'username.unique' => 'Tên đăng nhập đã thuộc về người khác',
            'email.unique' => 'Email này đã thuộc về người khác',
        ]);

        try {
            $user = User::find($id);
            if (!$user) {
                return redirect()->route('user.index')->with('error', 'Không tìm thấy thành viên!');
            }

            // Cập nhật thông tin cơ bản
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->role = $request->role;
            $user->status = $request->status;
            $user->updated_by = 1; // Giả định admin ID 1 đang sửa

            // Xử lý mật khẩu: Chỉ cập nhật nếu người dùng có nhập
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Xử lý ảnh đại diện
            if ($request->hasFile('avatar')) {
                // 1. Xóa ảnh cũ nếu có (để tiết kiệm dung lượng)
                if ($user->avatar && file_exists(public_path('images/user/' . $user->avatar))) {
                    unlink(public_path('images/user/' . $user->avatar));
                }

                // 2. Upload ảnh mới
                $file = $request->file('avatar');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/user'), $filename);
                $user->avatar = $filename;
            }

            $user->save();

            return redirect()->route('user.index')->with('success', 'Cập nhật thành viên thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    }
    // 6. Xóa thành viên
    public function destroy($id)
    {
        try {
            // Không cho xóa tài khoản Super Admin (ID = 1)
            if ($id == 1) {
                return redirect()->route('user.index')->with('error', 'Không thể xóa tài khoản Quản trị viên cấp cao (ID 1)!');
            }

            $user = User::find($id);
            if (!$user) {
                return redirect()->route('user.index')->with('error', 'Thành viên không tồn tại!');
            }

            // 1. Xóa ảnh đại diện trong thư mục public (nếu có)
            if ($user->avatar && file_exists(public_path('images/user/' . $user->avatar))) {
                unlink(public_path('images/user/' . $user->avatar));
            }

            // 2. Xóa bản ghi trong Database
            $user->delete();

            return redirect()->route('user.index')->with('success', 'Xóa thành viên thành công!');
        } catch (\Exception $e) {
            return redirect()->route('user.index')->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }
}
