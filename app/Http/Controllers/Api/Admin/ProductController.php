<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Lấy danh sách sản phẩm (index) - Hỗ trợ tìm kiếm, trả về chi tiết đầy đủ cho Frontend
     */
    public function index(Request $request)
    {
        try {
            $query = Product::orderBy('id', 'desc');

            // Thêm logic tìm kiếm nếu có tham số 'search'
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            }

            $products = $query->get();

            $data = $products->map(function($product) {
                // Sử dụng optional() để truy cập Category an toàn (ngay cả khi null)
                $categoryName = optional($product->category)->name;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price_buy,
                    'category_name' => $categoryName ?? 'N/A',
                    'category_id' => $product->category_id,
                    'image' => $product->thumbnail_url, // Accessor an toàn
                    'status' => $product->status,

                    // Thêm các trường chi tiết cho Modal Edit (Đã FIX)
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'content' => $product->content,
                ];
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Product Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải dữ liệu sản phẩm từ server.'], 500);
        }
    }

    /**
     * Hiển thị chi tiết một sản phẩm (show)
     */
    public function show($id)
    {
        try {
            // Tải chi tiết sản phẩm và các mối quan hệ
            $product = Product::with(['images', 'sale', 'productAttributes'])->findOrFail($id);
            return response()->json($product);
        } catch (\Exception $e) {
            \Log::error("Product Show Error for ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải chi tiết sản phẩm từ server.'], 500);
        }
    }

    /**
     * Thêm mới sản phẩm (STORE)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price_buy' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'slug' => 'required|string|unique:products,slug', // Cần slug khi store
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except('image');
        $data['slug'] = Str::slug($data['name']); // Tự động tạo slug nếu Frontend không gửi (hoặc gửi rỗng)
        $data['created_by'] = $request->user() ? $request->user()->id : null;

        DB::beginTransaction();
        try {
            $thumbnailPath = null;
            if ($imageFile) {
                $thumbnailPath = $imageFile->store('images/products', 'public');
            }
            $data['thumbnail'] = $thumbnailPath; // Lưu đường dẫn tương đối

            $product = Product::create($data);
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Thêm sản phẩm mới thành công!', 'product' => $product], 201);

        } catch (\Exception $e) {
            if ($thumbnailPath) { Storage::disk('public')->delete($thumbnailPath); }
            DB::rollBack();
            \Log::error("Product Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi thêm sản phẩm.'], 500);
        }
    }

    /**
     * Cập nhật thông tin sản phẩm (UPDATE)
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)], // Slug required khi update
            'price_buy' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
        ];

        if ($request->hasFile('image')) {
             $rules['image'] = 'image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageFile = $request->file('image');
        $data = $request->except(['_method', 'image']);
        $originalImagePath = $product->thumbnail;

        // Đảm bảo slug được cập nhật (nếu không có thì đã bị lỗi validation)

        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        DB::beginTransaction();
        try {
            $filePath = null;
            if ($imageFile) {
                if ($originalImagePath) {
                    Storage::disk('public')->delete($originalImagePath);
                }

                $filePath = $imageFile->store('images/products', 'public');
                $data['thumbnail'] = $filePath;
            } else {
                $data['thumbnail'] = $originalImagePath;
            }

            $product->update($data);
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Cập nhật sản phẩm thành công!', 'product' => $product]);

        } catch (\Exception $e) {
            if ($filePath) { Storage::disk('public')->delete($filePath); }
            DB::rollBack();
            \Log::error("Product Update Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi cập nhật sản phẩm.'], 500);
        }
    }

    /**
     * Xóa sản phẩm (DESTROY)
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $product->delete();
            DB::commit();
            return response()->json([ 'status' => true, 'message' => 'Xóa sản phẩm thành công (Soft Deleted).', 'product_id' => $id ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Product Delete Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi xóa sản phẩm.'], 500);
        }
    }
}
