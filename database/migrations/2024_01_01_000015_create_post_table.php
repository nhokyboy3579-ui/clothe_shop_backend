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
   Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('topic_id')->nullable()->constrained('topics')->onDelete('set null');
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('image')->nullable();
    $table->longText('content');
    $table->text('description')->nullable();
    $table->enum('type', ['post', 'page'])->default('post');
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
        Schema::dropIfExists('post');
    }
};
