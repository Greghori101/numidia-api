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
            $table->integer('month')->default(1);
            $table->string('status')->default("pending");
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('paid_price')->default(0);
            $table->datetime('pay_date')->nullable();
            $table->integer('teacher_percentage')->default(0);
            $table->text('notes')->nullable();

            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students');
            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');

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
