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
   Schema::create('menus', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('link');
    $table->enum('type', ['category', 'topic', 'page', 'custom']);
    $table->unsignedBigInteger('table_id')->default(0); // ID của bảng tương ứng (category_id...)

    $table->unsignedBigInteger('parent_id')->default(0);
    $table->unsignedInteger('sort_order')->default(0);
    $table->enum('position', ['mainmenu', 'footermenu'])->default('mainmenu');
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
        Schema::dropIfExists('menu');
    }
};
