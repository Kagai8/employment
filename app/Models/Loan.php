<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'amount', 'interest', 'start_date', 'status','installments','comapny_id',];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function installmentRecords()
{
    return $this->hasMany(Installment::class,'loan_id');
}

    protected static function boot()
    {
        parent::boot();

        static::created(function ($loan) {
            // Save each installment as a separate row
            foreach ($loan->installments as $installment) {
                Installment::create([
                    'loan_id' => $loan->id,
                    'amount' => $installment['amount'],
                    'due_date' => $installment['due_date'],
                    'status' => 'unpaid',
                ]);
            }
        });
    }

    protected static function booted()
{
    static::creating(function ($loan) {
        $loan->balance = $loan->amount; // ✅ Set balance equal to loan amount
    });
}

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected $casts = [
        'installments' => 'array', // Automatically converts to/from JSON

    ];


}
