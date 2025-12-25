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
    Schema::create('product_sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->decimal('price_sale', 12, 2);
    $table->dateTime('date_begin');
    $table->dateTime('date_end');
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
        Schema::dropIfExists('product_sale');
    }
};
