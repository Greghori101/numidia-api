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
        Schema::create('presence_student', function (Blueprint $table) {
            $table->id();
            
            $table->string('status')->default('present');

            $table->uuid('presence_id')->nullable();
            $table->foreign('presence_id')->references('id')->on('presences')->onDelete('cascade');
            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_student');
    }
};
