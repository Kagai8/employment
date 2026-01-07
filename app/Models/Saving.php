<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'salary_id',
        'amount',
        'notes',
        'employee_savings_plan_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salary(): BelongsTo
    {
        return $this->belongsTo(Salary::class);
    }


}
