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
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('repeating')->default('week');
            $table->string('type')->default('normal');
            $table->bigInteger('price');

            $table->uuid('group_id')->nullable();
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
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
