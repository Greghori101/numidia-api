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
        Schema::create('receipt_services', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('text');
            $table->integer('qte')->default(1);
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->text('notes')->nullable();
            
            $table->uuid('receipt_id')->nullable();
            $table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_services');
    }
};
