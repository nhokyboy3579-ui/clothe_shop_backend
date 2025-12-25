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
    Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('name');
    $table->string('email');
    $table->string('phone', 20);
    $table->text('content');
    $table->unsignedBigInteger('reply_id')->nullable(); // ID của liên hệ trả lời
    $table->unsignedTinyInteger('status')->default(1); // 1: Chưa xử lý, 2: Đã xử lý

    $table->unsignedBigInteger('updated_by')->nullable(); // Người trả lời
    $table->timestamps();
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact');
    }
};
