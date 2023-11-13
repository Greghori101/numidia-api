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
        Schema::create('amphis', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->integer('capacity');
            $table->string('location');
            $table->string('name');
            $table->integer('nb_columns');
            $table->integer('nb_rows');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amphis');
    }
};
