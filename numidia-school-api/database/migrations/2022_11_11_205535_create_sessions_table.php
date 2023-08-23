<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table
                ->uuid('id')
                ->primary()
                ->unique();
            $table->string('classroom');
            $table->string('starts_at')->nullable();
            $table->string('ends_at')->nullable();
            $table->string('repeating')->default('weekly');
            $table->string('type')->default('normal');

            $table->uuid('group_id')->nullable();
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

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
