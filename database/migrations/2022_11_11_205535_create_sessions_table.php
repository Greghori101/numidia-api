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
        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('classroom');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('state')->default('pending');

            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');
            $table->uuid('teacher_id')->nullable();
            $table->foreign('teacher_id')->references('id')->on('teachers');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classrooms');
    }
};
