<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollPdfController extends Controller
{
    public function generatePayslip($salaryId)
    {
        $salary = Salary::with('employee')->findOrFail($salaryId);

        $extraEarnings = is_array($salary->extra_earnings) ? $salary->extra_earnings : json_decode($salary->extra_earnings, true);
        $extraDeductions = is_array($salary->extra_deductions) ? $salary->extra_deductions : json_decode($salary->extra_deductions, true);

        $pdf = Pdf::loadView('pdf.payroll', compact('salary', 'extraEarnings', 'extraDeductions'));

        return $pdf->stream('Payslip_' . $salary->employee->first_name . '.pdf');
    }
}
