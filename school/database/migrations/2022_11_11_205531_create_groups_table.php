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
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('module');
            $table->string('type');
            $table->integer('capacity');
            $table->integer('nb_session');
            $table->unsignedBigInteger('price_per_month');
            $table->integer('current_nb_session')->default(1);
            $table->integer('rest_session');
            $table->string('annex')->nullable();

            $table->uuid('teacher_id')->nullable();
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            $table->uuid('level_id')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
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
        Schema::dropIfExists('groups');
    }
};
