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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();

            $table->string('status')->default('pending');
            $table->bigInteger('amount')->default(0);

            $table->uuid('to')->nullable();
            $table->foreign('to')->references('id')->on('wallets')->onDelete('cascade');
            $table->uuid('from')->nullable();
            $table->foreign('from')->references('id')->on('wallets')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
