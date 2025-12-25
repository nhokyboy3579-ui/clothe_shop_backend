<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Liên kết người mua (Nullable để hỗ trợ khách vãng lai - Guest Checkout)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Thông tin người nhận hàng (Snapshot từ form Checkout)
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20);
            $table->string('shipping_address'); // Địa chỉ đầy đủ ghép từ Tỉnh/Huyện/Xã
            $table->text('note')->nullable();

            // Thanh toán
            $table->string('payment_method')->default('COD'); // COD, BANKING, MOMO...
            $table->string('payment_status')->default('Unpaid'); // Unpaid, Paid, Refunded

            // Tài chính
            $table->decimal('subtotal', 12, 2)->default(0); // Tiền hàng
            $table->decimal('shipping_fee', 12, 2)->default(0); // Phí ship
            $table->decimal('total_amount', 12, 2)->default(0); // Tổng thanh toán (Hàng + Ship - Voucher)

            // Trạng thái đơn hàng: 1: Mới/Chờ xác nhận, 2: Đang xử lý, 3: Đang giao, 4: Hoàn thành, 5: Hủy
            $table->unsignedTinyInteger('status')->default(1);

            // Log quản trị
            $table->timestamps();
            $table->softDeletes(); // Nên có soft delete để không mất lịch sử đơn hàng
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
