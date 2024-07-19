<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('name');
            $table->integer('purchase_price');
            $table->integer('price');
            $table->integer('qte')->default(1);
            $table->string('description')->nullable();
            $table->string('level');
            $table->string('tags')->nullable();
            $table->timestamp('purchase_date')->nullable();
            

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
