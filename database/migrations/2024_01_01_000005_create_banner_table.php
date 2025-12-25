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
    Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('image');
    $table->string('link')->nullable();
    $table->enum('position', ['slideshow', 'ads'])->default('slideshow');
    $table->unsignedInteger('sort_order')->default(0);
    $table->text('description')->nullable();
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
        Schema::dropIfExists('banner');
    }
};
