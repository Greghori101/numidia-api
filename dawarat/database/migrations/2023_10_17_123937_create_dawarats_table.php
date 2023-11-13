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
        Schema::create('dawarats', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('title');
            $table->bigInteger('price')->default(0);
            $table->datetime('date')->nullable();

            $table->uuid('amphi_id')->nullable();
            $table->foreign('amphi_id')->references('id')->on('amphis');
            $table->uuid('teacher_id')->nullable();
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dawarats');
    }
};
