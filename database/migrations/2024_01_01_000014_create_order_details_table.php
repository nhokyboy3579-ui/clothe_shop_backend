<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();

            // Liên kết với bảng orders (Xóa order thì xóa luôn chi tiết)
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // Liên kết với sản phẩm (Nếu sản phẩm bị xóa, set null để giữ lịch sử)
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');

            // Lưu tên sản phẩm tại thời điểm mua (Phòng trường hợp sản phẩm gốc bị đổi tên hoặc xóa)
            $table->string('product_name');

            // Lưu biến thể nếu có (Màu sắc, Size...) - Có thể lưu JSON hoặc String
            $table->string('variant')->nullable();

            // Số lượng và Giá
            $table->integer('quantity');
            $table->decimal('price', 12, 2); // Giá bán tại thời điểm mua
            $table->decimal('total', 12, 2); // quantity * price

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
