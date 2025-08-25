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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date'); // The date of attendance
            $table->enum('status', ['Present', 'Absent', 'Late', 'On Leave'])->default('Present');
            $table->time('clock_in')->nullable(); // Optional clock-in time
            $table->time('clock_out')->nullable(); // Optional clock-out time
            $table->text('remarks')->nullable(); // Any remarks from the admin
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
