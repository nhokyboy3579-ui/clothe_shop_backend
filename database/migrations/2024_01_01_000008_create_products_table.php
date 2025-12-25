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
    Schema::create('products', function (Blueprint $table) {
    $table->id();

    // Liên kết Category
    $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');

    $table->string('name');
    $table->string('slug')->unique();
    $table->string('thumbnail');
    $table->longText('content')->nullable();
    $table->text('description')->nullable();
    $table->decimal('price_buy', 12, 2); // Giá bán thường
    $table->unsignedTinyInteger('status')->default(1);

    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps();
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
