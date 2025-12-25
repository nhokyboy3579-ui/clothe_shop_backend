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
    Schema::create('config', function (Blueprint $table) {
        $table->id();
        $table->string('site_name');
        $table->string('email');
        $table->string('phone');
        $table->string('hotline');
        $table->string('address');
        $table->unsignedTinyInteger('status')->default(1);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config');
    }
};
