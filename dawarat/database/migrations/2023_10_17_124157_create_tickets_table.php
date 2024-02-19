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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->integer('row');
            $table->integer('seat');
            $table->string('section');
            $table->string('title');
            $table->bigInteger('price');
            $table->boolean('paid');
            $table->string('location');
            $table->bigInteger('discount');
            $table->datetime('date')->nullable();

            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students');
            $table->uuid('presence_id')->nullable();
            $table->foreign('presence_id')->references('id')->on('presences');
            $table->uuid('dawarat_id')->nullable();
            $table->foreign('dawarat_id')->references('id')->on('dawarats');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
