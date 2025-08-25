<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = ['loan_id', 'amount', 'due_date', 'salary_id', 'date_paid', 'status'];

    public function loan() {
        return $this->belongsTo(Loan::class);
    }

    public function salary()
{
    return $this->belongsTo(Salary::class, 'salary_id');
}

    protected static function booted()
    {
        static::saved(function ($salary) {
            $salary->updateLoanInstallment();
        });
    }

    public function updateLoanInstallment()
    {
        if ($this->deduct_loan === 'yes') {
            $installment = Installment::where('employee_id', $this->employee_id)
                ->where('status', 'unpaid')
                ->orderBy('due_date', 'asc')
                ->first();

            if ($installment) {
                $installment->salary_id = $this->id;
                $installment->date_paid = now();
                $installment->status = 'paid';
                $installment->save();
            }
        }
    }
}
