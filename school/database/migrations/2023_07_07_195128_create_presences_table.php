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
        Schema::create('presences', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->integer('month')->default(1);
            $table->integer('session_number')->default(1);
            $table->string('status')->default('pending');
            $table->string('type')->default('normal');
            $table->datetime('starts_at')->nullable();
            $table->datetime('ends_at')->nullable();

            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups');
            $table->uuid('session_id')->nullable();
            $table->foreign('session_id')->references('id')->on('sessions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
