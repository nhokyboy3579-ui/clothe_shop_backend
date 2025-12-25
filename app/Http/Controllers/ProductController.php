<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category; // Import Category để hiển thị dropdown
use App\Models\ProductStore;

class ProductController extends Controller
{
    // 1. Danh sách (Có Search + Sort + Pagination)
    public function index()
    {
        $products = Product::with('category') // Eager Loading giảm query
            ->filter() // Gọi scopeFilter trong Model
            ->paginate(5) // Phân trang 5 item
            ->withQueryString(); // Giữ lại tham số tìm kiếm khi chuyển trang

        // Lấy danh mục để làm bộ lọc
        $categories = Category::where('status', 1)->get();

        return view('admin.product.index', compact('products', 'categories'));
    }

    // 2. Form thêm mới
    public function create()
    {
        $categories = Category::where('status', 1)->get();
        return view('admin.product.create', compact('categories'));
    }

    // 3. Xử lý thêm mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:5|unique:products,name',
            'category_id' => 'required',
            'price_buy' => 'required|numeric|min:0',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'slug' => 'required|unique:products,slug'
        ]);

        try {
            $product = new Product();
            $product->fill($request->all());
            $product->created_by = 1; // Admin ID

            // Upload ảnh
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/product'), $filename);
                $product->thumbnail = $filename;
            }

            $product->save();
            return redirect()->route('product.index')->with('success', 'Thêm sản phẩm thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    // 4. Form chỉnh sửa
    public function edit($id)
    {
        $product = Product::find($id);
        $categories = Category::where('status', 1)->get();
        if (!$product) return redirect()->route('product.index')->with('error', 'Không tồn tại!');

        return view('admin.product.edit', compact('product', 'categories'));
    }

    // 5. Cập nhật
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:5|unique:products,name,'.$id,
            'price_buy' => 'required|numeric',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        try {
            $product = Product::find($id);
            $product->fill($request->except('thumbnail')); // Update thông tin trừ ảnh
            $product->updated_by = 1;

            if ($request->hasFile('thumbnail')) {
                // Xóa ảnh cũ
                if (file_exists(public_path('images/product/' . $product->thumbnail))) {
                    unlink(public_path('images/product/' . $product->thumbnail));
                }
                // Upload ảnh mới
                $file = $request->file('thumbnail');
                $filename = date('YmdHis') . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/product'), $filename);
                $product->thumbnail = $filename;
            }

            $product->save();
            return redirect()->route('product.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // 6. Xóa
    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            if ($product->thumbnail && file_exists(public_path('images/product/' . $product->thumbnail))) {
                unlink(public_path('images/product/' . $product->thumbnail));
            }
            $product->delete();
            return redirect()->route('product.index')->with('success', 'Xóa thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
#Chi tiết sản phẩm
public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm'], 404);
        }

        // Xử lý dữ liệu trả về cho đẹp
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price_buy, // Giá bán
            'price_original' => $product->price_root, // Giá gốc (nếu có để gạch ngang)

            // Xử lý ảnh: Nếu là link online giữ nguyên, nếu là file thì nối đường dẫn storage
            'image' => $product->thumbnail ? (
                str_contains($product->thumbnail, 'http')
                ? $product->thumbnail
                : asset('storage/' . $product->thumbnail)
            ) : null,

            'category' => $product->category_id, // Hoặc lấy tên category nếu có relation
            'description' => $product->content ?? $product->description ?? 'Đang cập nhật mô tả...',
            'slug' => $product->slug
        ];

        return response()->json($data);
    }
}
