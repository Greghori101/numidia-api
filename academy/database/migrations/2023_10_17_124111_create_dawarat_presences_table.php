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
        Schema::create('dawarat_presences', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('status')->default('absent');

            $table->uuid('dawarat_id')->nullable();
            $table->foreign('dawarat_id')->references('id')->on('groups');
            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
