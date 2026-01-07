<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSavingsPlan extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'total_amount', // We fill this, and update it later
        'status',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relationship to the ledger for easy reporting/viewing
    public function savingsTransactions(): HasMany
    {
        return $this->hasMany(Saving::class, 'employee_savings_plan_id');
    }
}
