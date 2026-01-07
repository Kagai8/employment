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
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('saving_deduction', 10, 2)->default(0)->after('advance_deduction')->nullable();

            // Field to store the plan ID (optional, but good for tracking)
            $table->foreignId('employee_savings_plan_id')->nullable()->constrained()->onDelete('set null')->after('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropForeign(['employee_savings_plan_id']);
            $table->dropColumn(['saving_deduction', 'employee_savings_plan_id']);
        });
    }
};
