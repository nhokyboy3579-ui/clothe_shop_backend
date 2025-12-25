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
    Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique(); // Username không được trùng
    $table->string('email')->unique();
    $table->string('phone', 20)->nullable();
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->enum('role', ['admin', 'customer'])->default('customer');
    $table->unsignedTinyInteger('status')->default(1);

    // Audit logs
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps(); // Tự động tạo created_at và updated_at
});
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
