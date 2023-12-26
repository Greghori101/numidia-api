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
        Schema::create('answers', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string("answer");
            $table->integer("score")->default(0);

            $table->uuid('exam_id')->nullable();
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->uuid('question_id')->nullable();
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
