<?php

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSale; // Import Model Sale
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Carbon\Carbon; // Import Carbon để xử lý ngày giờ

class UserProductController extends Controller
{
    /**
     * 1. Lấy danh sách sản phẩm Flash Sale (MỚI THÊM)
     * Chỉ lấy các sale đang diễn ra (trong khoảng thời gian begin - end)
     */
    public function getFlashSaleProducts()
    {
        try {
            $now = Carbon::now();

            // Lấy các bản ghi Sale thỏa mãn điều kiện thời gian và status
            $sales = ProductSale::with('product') // Eager load product
                ->where('status', 1) // Sale đang bật
                ->where('date_begin', '<=', $now) // Đã bắt đầu
                ->where('date_end', '>=', $now)   // Chưa kết thúc
                ->whereHas('product', function ($q) {
                    $q->where('status', 0); // Đảm bảo sản phẩm gốc cũng đang Active (0)
                })
                ->orderBy('id', 'desc')
                ->get();

            // Map dữ liệu trả về
            $data = $sales->map(function ($sale) {
                $product = $sale->product;

                // Tính % giảm giá (để frontend đỡ phải tính)
                $originalPrice = $product->price_buy;
                $salePrice = $sale->price_sale;
                $percent = 0;
                if ($originalPrice > 0) {
                    $percent = round((($originalPrice - $salePrice) / $originalPrice) * 100);
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price_original' => $originalPrice, // Giá gốc
                    'price_sale' => $salePrice,         // Giá sale
                    'discount_percent' => $percent,
                    'image' => $product->thumbnail_url, // Accessor xử lý ảnh
                    'category_name' => optional($product->category)->name,
                    'sale_info' => [
                        'date_end' => $sale->date_end, // Để frontend làm đồng hồ đếm ngược
                        'sale_id' => $sale->id
                    ]
                ];
            });

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $e) {
            \Log::error("Get Flash Sale Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải Flash Sale.', 'data' => []], 500);
        }
    }

    /**
     * 2. Lấy danh sách sản phẩm thường (ĐÃ SỬA)
     * Logic: Lấy sản phẩm Active TRỪ ĐI các sản phẩm đang nằm trong Flash Sale
     */
    public function indexActiveProducts()
    {
        try {
            $now = Carbon::now();

            // B1: Tìm ID của các sản phẩm ĐANG có Flash Sale hợp lệ
            $flashSaleProductIds = ProductSale::where('status', 1)
                ->where('date_begin', '<=', $now)
                ->where('date_end', '>=', $now)
                ->pluck('product_id') // Chỉ lấy cột product_id
                ->toArray();

            // B2: Lấy danh sách sản phẩm, TRỪ các ID vừa tìm được
            $products = Product::where('status', 0) // Chỉ lấy active
                ->whereNotIn('id', $flashSaleProductIds) // <--- LOẠI BỎ SẢN PHẨM FLASH SALE
                ->orderBy('id', 'desc')
                ->get();

            // Map dữ liệu đầu ra an toàn
            $data = $products->map(function ($product) {
                $categoryName = optional($product->category)->name;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price_buy,
                    'category_name' => $categoryName ?? 'N/A',
                    'category_id' => $product->category_id,
                    'image' => $product->thumbnail_url,
                ];
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Public Active Products Index Error: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi tải danh sách sản phẩm.'], 500);
        }
    }


    /**
     * 3. Hiển thị chi tiết một sản phẩm (GIỮ NGUYÊN)
     */
    public function show($id)
    {
        try {
            // 1. Tìm sản phẩm Active (status = 0) cùng các quan hệ
            $product = Product::with(['images', 'sale', 'productAttributes.attribute', 'category'])
                ->where('status', 0)
                ->findOrFail($id);

            // 2. LOGIC KIỂM TRA SALE
            $salePrice = null;
            $saleInfo = null;
            $now = \Carbon\Carbon::now();

            // Kiểm tra: Có bản ghi sale + Sale đang bật (1) + Trong khung giờ
            if ($product->sale && $product->sale->status == 1) {
                $start = \Carbon\Carbon::parse($product->sale->date_begin);
                $end   = \Carbon\Carbon::parse($product->sale->date_end);

                if ($now->gte($start) && $now->lte($end)) {
                    $salePrice = $product->sale->price_sale;

                    // Gửi thêm thông tin để làm đồng hồ đếm ngược
                    $saleInfo = [
                        'date_end' => $product->sale->date_end,
                        'name' => 'Flash Sale'
                    ];
                }
            }

            // 3. XỬ LÝ THUỘC TÍNH (Size, Color...)
            $groupedAttributes = [];
            if ($product->productAttributes) {
                foreach ($product->productAttributes as $item) {
                    // Đảm bảo có attribute cha
                    if ($item->attribute) {
                        $name = $item->attribute->name;
                        $value = $item->value;
                        // Gom nhóm: Ví dụ 'Size' => ['M', 'L', 'XL']
                        if (!isset($groupedAttributes[$name])) {
                            $groupedAttributes[$name] = [];
                        }
                        if (!in_array($value, $groupedAttributes[$name])) {
                            $groupedAttributes[$name][] = $value;
                        }
                    }
                }
            }

            // 4. CHUẨN BỊ DỮ LIỆU TRẢ VỀ
            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description ?? $product->content,

                // Giá hiển thị
                'price' => $product->price_buy,      // Giá gốc (luôn có)
                'sale_price' => $salePrice,          // Giá sale (null nếu không sale)

                // % Giảm giá (Frontend dùng để hiện tem)
                'discount_percent' => ($salePrice && $product->price_buy > 0)
                    ? round((($product->price_buy - $salePrice) / $product->price_buy) * 100)
                    : 0,

                // Thông tin phụ
                'category_name' => optional($product->category)->name,
                'category_id' => $product->category_id,
                'image' => $product->thumbnail_url, // Link ảnh đại diện

                // Album ảnh chi tiết
                'gallery' => $product->images->map(function ($img) {
                    // Xử lý link ảnh gallery (tương tự thumbnail)
                    $path = $img->image;
                    if ($path && !str_contains($path, 'http')) {
                        return asset('storage/' . $path);
                    }
                    return $path;
                }),

                // Dữ liệu thuộc tính & Sale
                'attributes' => $groupedAttributes,
                'sale_info' => $saleInfo,
            ];

            return response()->json(['status' => true, 'data' => $data]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.'], 404);
        } catch (\Exception $e) {
            \Log::error("Product Detail Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi server'], 500);
        }
    }
}
