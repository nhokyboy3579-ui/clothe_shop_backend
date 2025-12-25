<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    // Xóa sản phẩm -> Xóa luôn ảnh
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->string('image');
    $table->string('alt')->nullable();
    $table->timestamps();
});
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_image');
    }
};
