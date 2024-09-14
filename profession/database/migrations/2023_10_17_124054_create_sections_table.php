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
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->integer('ending_row');
            $table->integer('ending_column');
            $table->integer('starting_column');
            $table->integer('starting_row');

            $table->uuid('amphi_id')->nullable();
            $table->foreign('amphi_id')->references('id')->on('amphis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
