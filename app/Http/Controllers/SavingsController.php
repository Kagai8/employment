<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSavingsPlan;
use Barryvdh\DomPDF\Facade\Pdf; // Ensure you have this package installed

class SavingsController extends Controller
{
    /**
     * Generates a PDF summary for an Employee Savings Plan.
     */
    public function generate($planId)
    {
        // Fetch the Master Plan record, eager-loading the employee and all transaction details
        $plan = EmployeeSavingsPlan::with([
    'employee.company',
    'savingsTransactions' => function ($query) {
        $query->orderBy('created_at', 'asc');
    },
])->findOrFail($planId);

        // Calculate the total contribution count
        $contributionCount = $plan->savingsTransactions->count();

        // Calculate the total saved amount (though it's already in $plan->total_amount)
        $totalAmount = $plan->total_amount;

        // Load the blade view for the PDF and pass the data
        $pdf = Pdf::loadView('pdf.savings-summary', compact('plan', 'contributionCount', 'totalAmount'));

        // Define the filename
        $fileName = "Savings_Summary_{$plan->employee->first_name}_{$plan->employee->last_name}.pdf";

        return $pdf->stream($fileName);
    }
}
