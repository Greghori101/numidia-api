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
        Schema::create('checkouts', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->unsignedBigInteger('price');
            $table->integer('nb_session');
            $table->string('status')->default("pending");
            $table->unsignedBigInteger('discount')->default(0);
            $table->datetime('pay_date')->nullable();
            $table->datetime('date')->nullable();
            $table->boolean('paid')->default(false);

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students');
            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');

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
        Schema::dropIfExists('checkouts');
    }
};
