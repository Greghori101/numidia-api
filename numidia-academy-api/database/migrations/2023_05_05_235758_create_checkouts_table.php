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
            $table->bigInteger('price');
            $table->date('date');
            $table->boolean('payed')->default(false);

            $table->uuid('student_id')->nullable();
            $table->foreign('student_id')->references('id')->on('students'); 
            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');
            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
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
