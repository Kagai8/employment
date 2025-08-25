<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip For {{$salary->employee->first_name}} {{$salary->employee->last_name}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            padding: 10px;
            background: #007BFF;
            color: white;
            border-radius: 5px;
        }
        .company-info {
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .details-table, .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .details-table td, .salary-table th, .salary-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .salary-table th {
            background-color: #007BFF;
            color: white;
        }
        .salary-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .highlight {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        .signature {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Company Details at the Top Left -->
        <div class="company-details" style="text-align: left; margin-bottom: 20px;">
            <h2 style="margin: 0;">{{ $salary->company->company_name }}</h2>
            <p style="margin: 2px 0;">{{ $salary->company->company_address }}</p>
            <p style="margin: 2px 0;">Phone: {{ $salary->company->company_phone }}</p>
            <p style="margin: 2px 0;">Email: {{ $salary->company->company_email }}</p>
        </div>
        <div class="header">Pay Slip for {{ $salary->created_at->format('M Y') }}</div>


        <!-- Employee Details -->
        <table class="details-table">
            <tr>
                <td><strong>Name:</strong> {{ $salary->employee->first_name }} {{ $salary->employee->last_name }}</td>
                <td><strong>Position:</strong> {{ $salary->employee->position }}</td>
            </tr>
            <tr>
                <td><strong>Department:</strong> {{ $salary->employee->department }}</td>
                <td><strong>Contract Type:</strong> {{ $salary->employee->contract_type }}</td>
            </tr>
        </table>

        <!-- Payment Method -->
        <table class="details-table">
            <tr style="background-color: #007BFF; color: white; text-align: left;">
                <th colspan="3">Payment Method</th>
            </tr>
            @if($salary->payment_method === 'bank')
            <tr>
                <td><strong>Bank:</strong> {{ $salary->employee->bank_name }}</td>
                <td><strong>Branch:</strong> {{ $salary->employee->bank_branch }}</td>
                <td><strong>Account No:</strong> {{ $salary->employee->bank_account_number }}</td>
            </tr>
            @elseif($salary->payment_method === 'mpesa')
            <tr>
                <td colspan="3"><strong>M-Pesa Number:</strong> {{ $salary->mpesa_number }}</td>
            </tr>
            @endif
        </table>

        <!-- Salary Breakdown -->
        <table class="salary-table">
            <tr>
                <th>Earnings</th>
                <th>Amount (KES)</th>
                <th>Deductions</th>
                <th>Amount (KES)</th>
            </tr>
            <tr>
                <td>Basic Salary</td>
                <td>{{ number_format($salary->basic_salary, 2) }}</td>
                <td>PAYE</td>
                <td>{{ number_format($salary->paye_tax, 2) }}</td>
            </tr>
            <tr>
                <td>House Allowance</td>
                <td>{{ number_format($salary->house_allowance, 2) }}</td>
                <td>NHIF</td>
                <td>{{ number_format($salary->sha_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>Transport Allowance</td>
                <td>{{ number_format($salary->transport_allowance, 2) }}</td>
                <td>NSSF</td>
                <td>{{ number_format($salary->nssf_contribution, 2) }}</td>
            </tr>

            @php
                $maxRows = max(count($extraEarnings), count($extraDeductions));
            @endphp

            @for ($i = 0; $i < $maxRows; $i++)
            <tr>
                <td>{{ $extraEarnings[$i]['name'] ?? '' }}</td>
                <td>{{ isset($extraEarnings[$i]) ? number_format($extraEarnings[$i]['amount'], 2) : '' }}</td>
                <td>{{ $extraDeductions[$i]['name'] ?? '' }}</td>
                <td>{{ isset($extraDeductions[$i]) ? number_format($extraDeductions[$i]['amount'], 2) : '' }}</td>
            </tr>
            @endfor

            @if($salary->loan_deduction > 0)
            <tr style="background-color: #f8d7da; color: #721c24;">
                <td><strong>Loan Deduction</strong></td>
                <td></td>
                <td><strong>{{ number_format($salary->loan_deduction, 2) }}</strong></td>
            </tr>
            @endif
            @if($salary->advance_deduction > 0)
            <tr style="background-color: #f8d7da; color: #721c24;">
                <td><strong>Advance Deduction</strong></td>
                <td></td>
                <td><strong>{{ number_format($salary->advance_deduction, 2) }}</strong></td>
            </tr>
            @endif

            <tr>
                <th>Total Earnings</th>
                <th>{{ number_format(
                    $salary->basic_salary +
                    $salary->house_allowance +
                    $salary->transport_allowance +
                    collect($extraEarnings)->sum('amount'), 2) }}
                </th>
                <th>Total Deductions</th>
                <th>{{ number_format(
                    $salary->paye_tax +
                    $salary->sha_contribution +
                    $salary->nssf_contribution +
                    collect($extraDeductions)->sum('amount') +
                    $salary->loan_deduction+
                    $salary->advance_deduction, 2) }}
                </th>
            </tr>

            <tr style="background-color: #28a745; color: white;">
                <th colspan="3">Net Pay</th>
                <th class="highlight">KES {{ number_format($salary->net_salary, 2) }}</th>
            </tr>
        </table>

        <!-- Processing Date -->
        <p><strong>Processed on:</strong> {{ $salary->created_at->format('d M Y, h:i A') }}</p>

        <!-- Signature Section -->
        <div class="signature">
            <table width="100%">
                <tr>
                    <td style="text-align: right;">Employer's Signature: _______________</td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>
