<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'extra_earnings',
        'paye_tax',
        'sha_contribution',
        'nssf_contribution',
        'extra_deductions',
        'net_salary',
        'payment_method',
        'mpesa_number',
        'company_id',
        'loan_deduction',
        'deduct_loan',
        'balance',
        'loan_id',
        'advance_deduction',
        'saving_deduction',
        'employee_savings_plan_id',
    ];

    protected $casts = [
        'extra_earnings' => 'array',
        'extra_deductions' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function advances()
    {
        return $this->hasMany(Advance::class, 'employee_id', 'employee_id')
            ->where('remaining_balance', '>', 0);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['deduct_loan'] === 'no') {
            $data['loan_deduction'] = 0;
        }

        return $data;
    }

    protected static function booted()
    {
        static::created(function ($salary) {

            Log::info("Processing Loan Deduction", [
                'salary_id' => $salary->id,
                'employee_id' => $salary->employee_id,
                'deduct_loan' => $salary->deduct_loan,
                'loan_id' => $salary->loan_id,
                'loan_deduction' => $salary->loan_deduction
            ]);

            // ===================== LOAN DEDUCTION =====================
            if ($salary->deduct_loan === 'yes' && $salary->loan_id) {
                $installment = Installment::where('loan_id', $salary->loan_id)
                    ->where('status', 'unpaid')
                    ->orderBy('due_date', 'asc')
                    ->first();

                if ($installment) {
                    $loan = Loan::find($salary->loan_id);

                    if ($loan) {
                        $loan->balance -= $salary->loan_deduction;
                        $loan->save();

                        $installment->update([
                            'salary_id' => $salary->id,
                            'date_paid' => now(),
                            'status' => 'paid',
                        ]);

                        if ($loan->balance <= 0) {
                            Installment::where('loan_id', $loan->id)
                                ->where('status', 'unpaid')
                                ->update([
                                    'salary_id' => $salary->id,
                                    'date_paid' => now(),
                                    'status' => 'paid',
                                ]);

                            $loan->update(['status' => 'completed']);
                        }
                    }
                }
            }

            // ===================== ADVANCE DEDUCTION =====================
            Log::info("Processing Advance Deduction", ['salary_id' => $salary->id]);

            $totalAdvances = $salary->employee
                ->advances()
                ->where('remaining_balance', '>', 0)
                ->sum('remaining_balance');

            $deductionAmount = min($salary->net_salary, $totalAdvances);

            if ($deductionAmount > 0) {
                $salary->update(['advance_deduction' => $deductionAmount]);

                foreach (
                    $salary->employee
                        ->advances()
                        ->where('remaining_balance', '>', 0)
                        ->orderBy('created_at')
                        ->get() as $advance
                ) {
                    if ($deductionAmount <= 0) {
                        break;
                    }

                    $deducted = min($deductionAmount, $advance->remaining_balance);

                    $advance->update([
                        'remaining_balance' => $advance->remaining_balance - $deducted,
                        'salary_id' => $salary->id,
                        'is_deducted' => ($advance->remaining_balance - $deducted) <= 0,
                    ]);

                    $deductionAmount -= $deducted;
                }
            }

            // ===================== SAVINGS DEDUCTION =====================
            $savingsDeduction = (float) ($salary->saving_deduction ?? 0);

            if ($savingsDeduction > 0) {

                // 1. Find existing non-withdrawn plan
                $plan = EmployeeSavingsPlan::where('employee_id', $salary->employee_id)
                    ->where('status', '!=', 'withdrawn')
                    ->first();

                if (!$plan) {
                    $plan = EmployeeSavingsPlan::create([
                        'employee_id' => $salary->employee_id,
                        'company_id' => $salary->company_id,
                        'total_amount' => 0,
                        'status' => 'active',
                    ]);
                }

                // 2. Only process if active
                if ($plan->status === 'active') {

                    // ✅ ONLY CHANGE IS HERE
                    Saving::create([
                        'employee_id' => $salary->employee_id,
                        'company_id' => $salary->company_id,
                        'salary_id' => $salary->id,
                        'employee_savings_plan_id' => $plan->id, // ✅ added safely
                        'amount' => $savingsDeduction,
                        'notes' => 'Automatic deduction from payroll.',
                    ]);

                    $plan->increment('total_amount', $savingsDeduction);

                    $plan->status = 'active';
                    $plan->save();
                }

                // 3. Link salary to plan (already existed)
                $salary->update([
                    'employee_savings_plan_id' => $plan->id
                ]);
            }
        });
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
