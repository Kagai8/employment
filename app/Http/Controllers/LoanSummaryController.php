<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LoanSummaryController extends Controller
{
    public function generate($loanId)
    {
        $loan = Loan::with([
            'employee',
            'company',
            'installmentRecords.salary' // Use the new relationship name
        ])->findOrFail($loanId);





        $pdf = Pdf::loadView('pdf.loan-summary', compact('loan'));

        return $pdf->stream("Loan_Summary_{$loan->employee->first_name}_{$loan->employee->last_name}.pdf");
    }

}
