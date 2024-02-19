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
        Schema::create('fee_inscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->unsignedBigInteger("total")->default(500);
            $table->datetime('date')->nullable();
            $table->datetime('pay_date')->nullable();
            $table->boolean('paid')->default(false);

            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_inscriptions');
    }
};
