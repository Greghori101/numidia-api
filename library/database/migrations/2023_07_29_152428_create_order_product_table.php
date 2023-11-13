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
        Schema::create('order_product', function (Blueprint $table) {
            $table->integer('qte')->default(0);
            $table->integer('price')->default(0);
            $table->uuid('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products');
            $table->primary(['order_id', 'product_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
