<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_student', function (Blueprint $table) {
            $table->id();
            
            $table->integer('first_month')->default(1);
            $table->integer('first_session')->default(1);
            $table->integer('last_month')->default(1);
            $table->integer('last_session')->default(1);
            $table->integer('nb_paid_session')->default(0);
            $table->integer('nb_session')->default(0);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('debt')->default(0);
            $table->unsignedBigInteger('discount')->default(0);

            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_student');
    }
};
