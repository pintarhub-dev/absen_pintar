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
        Schema::create('schedule_pattern_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_pattern_id')->constrained()->cascadeOnDelete();
            $table->integer('day_index')->index(); // Hari ke berapa dalam siklus (1 s/d cycle_length)
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->index(['schedule_pattern_id', 'day_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_pattern_details');
    }
};
