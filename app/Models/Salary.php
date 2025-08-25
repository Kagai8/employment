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
    ];

    protected $casts = [
        'extra_earnings' => 'array',
        'extra_deductions' => 'array',
        
    ];



    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }


    public function salaries(){
        return $this->hasMany(Salary::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function advances()
    {
        return $this->hasMany(Advance::class, 'employee_id', 'employee_id')
            ->where('remaining_balance', '>', 0); // Fetch only advances with a balance
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

        // ✅ Loan Deduction Logic (Your existing logic)
        if ($salary->deduct_loan === 'yes' && $salary->loan_id) {
            $installment = Installment::where('loan_id', $salary->loan_id)
                ->where('status', 'unpaid')
                ->orderBy('due_date', 'asc')
                ->first();

            if ($installment) {
                Log::info("Installment Found", [
                    'installment_id' => $installment->id,
                    'amount' => $installment->amount
                ]);

                // ✅ Deduct from Loan Balance
                $loan = Loan::find($salary->loan_id);
                if ($loan) {
                    Log::info("Before Deduction", [
                        'loan_id' => $loan->id,
                        'current_balance' => $loan->balance
                    ]);

                    $loan->balance -= $salary->loan_deduction;
                    $loan->save();

                    Log::info("After Deduction", [
                        'loan_id' => $loan->id,
                        'new_balance' => $loan->balance
                    ]);

                    // ✅ Mark Installment as Paid
                    $installment->update([
                        'salary_id' => $salary->id,
                        'date_paid' => now(),
                        'status' => 'paid',
                    ]);

                    Log::info("Installment Updated to Paid", ['installment_id' => $installment->id]);

                    // ✅ If Loan is Fully Paid, Mark Remaining Installments as Paid
                    if ($loan->balance <= 0) {
                        Installment::where('loan_id', $loan->id)
                            ->where('status', 'unpaid')
                            ->update([
                                'salary_id' => $salary->id,
                                'date_paid' => now(),
                                'status' => 'paid',
                            ]);

                        $loan->update(['status' => 'completed']);

                        Log::info("Loan Fully Paid. All Installments Marked as Paid.", ['loan_id' => $loan->id]);
                    }
                }
            } else {
                Log::warning("No unpaid installment found for loan.", ['loan_id' => $salary->loan_id]);
            }
        }

        // ✅ Advance Deduction Logic
        Log::info("Processing Advance Deduction", ['salary_id' => $salary->id]);

       // ✅ Advance Deduction Logic
       Log::info("Processing Advance Deduction", ['salary_id' => $salary->id]);

       // Get total outstanding advances
       $totalAdvances = $salary->employee->advances()->where('remaining_balance', '>', 0)->sum('remaining_balance');
       $deductionAmount = min($salary->net_salary, $totalAdvances); // Deduct only what can be covered

       if ($deductionAmount > 0) {
           // ✅ Update Salary with Advance Deduction
           $salary->update(['advance_deduction' => $deductionAmount]);

           // ✅ Process Each Advance Until Deduction is Covered
           foreach ($salary->employee->advances()->where('remaining_balance', '>', 0)->orderBy('created_at')->get() as $advance) {
               if ($deductionAmount <= 0) {
                   break;
               }

               $deducted = min($deductionAmount, $advance->remaining_balance);

               // ✅ Update Advance Entry
               $advance->update([
                   'remaining_balance' => $advance->remaining_balance - $deducted,
                   'salary_id' => $salary->id, // ✅ Ensure Salary ID is Assigned
                   'is_deducted' => ($advance->remaining_balance - $deducted) <= 0, // Mark as fully deducted if balance is 0
               ]);

               $deductionAmount -= $deducted;
           }
       }

       Log::info("Advance Deduction Completed", [
           'salary_id' => $salary->id,
           'remaining_net_salary' => $salary->net_salary
       ]);
    });
}






public function loan()
{
    return $this->belongsTo(Loan::class);
}



}
