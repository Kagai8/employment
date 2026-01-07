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
        Schema::create('employee_savings_plans', function (Blueprint $table) {
            $table->id();
            // Unique constraint ensures only one plan per employee
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');

            // ✅ THE RUNNING TOTAL: This is the field that will be updated automatically.
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_savings_plans');
    }
};
