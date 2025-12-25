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
    Schema::create('product_store', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->decimal('price_root', 12, 2); // Giá nhập
    $table->unsignedInteger('qty')->default(0); // Số lượng tồn
    $table->unsignedTinyInteger('status')->default(1);

    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
});
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_store');
    }
};
