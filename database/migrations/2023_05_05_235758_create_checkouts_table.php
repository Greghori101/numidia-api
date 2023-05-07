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
            $table->integer('price');
            $table->integer('nb_session');
            $table->integer('total');
            $table->integer('nb_month');
            $table->date('end_date');

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
