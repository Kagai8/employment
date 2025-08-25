<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_id',
        'amount',
        'remaining_balance',
        'request_date',
        'expected_deduction_date',
        'is_deducted',
        'company_id',

    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salary()
    {
        return $this->belongsTo(Salary::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    

    protected static function boot()
{
    parent::boot();
    static::creating(function ($advance) {
        $advance->remaining_balance = $advance->amount;
    });
}
}
