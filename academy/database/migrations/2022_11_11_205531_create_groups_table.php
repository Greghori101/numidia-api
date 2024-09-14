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
            $table->string('annex')->nullable();
            $table->string('type');
            $table->string('module');
            $table->integer('capacity');
            $table->integer('percentage');
            $table->unsignedBigInteger('price_per_month');
            $table->integer('nb_session');
            $table->string('main_session');
            $table->integer('current_month')->default(1);
            $table->integer('current_nb_session')->default(1);

            $table->uuid('teacher_id')->nullable();
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            $table->uuid('level_id')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            $table->uuid('amphi_id')->nullable();
            $table->foreign('amphi_id')->references('id')->on('amphis');

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
