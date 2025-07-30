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
        Schema::create('attendance_correct_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->string('reason');
            $table->string('status')->default('pending');
            $table->dateTime('fixed_clock_in')->nullable();
            $table->dateTime('fixed_clock_out')->nullable();
            $table->dateTime('fixed_break_start')->nullable();
            $table->dateTime('fixed_break_end')->nullable();
            $table->json('fixed_breaks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correct_requests');
    }

};
