<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Summary - {{ $loan->employee->first_name }} {{ $loan->employee->last_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { padding: 20px; }
        .header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        .details-table, .installments-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .details-table td, .installments-table th, .installments-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .installments-table th { background-color: #007BFF; color: white; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">Loan Summary for {{ $loan->employee->first_name }} {{ $loan->employee->last_name }}</div>

        <table class="details-table">
            <tr>
                <td><strong>Loan Amount:</strong> KES {{ number_format($loan->amount, 2) }}</td>
                <td><strong>Balance:</strong> KES {{ number_format($loan->balance, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($loan->start_date)->format('d M Y') ?? 'N/A' }}</td>
                <td><strong>End Date:</strong> {{ \Carbon\Carbon::parse(optional($loan->installmentRecords->last())->due_date)->format('d M Y') ?? 'N/A' }}</td>
            </tr>
        </table>

        <h3>Installment Details</h3>
        <table class="installments-table">
            <tr>
                <th>#</th>
                <th>Amount (KES)</th>
                <th>Status</th>
                <th>Date Paid</th>
                <th>Salary Used For</th>
            </tr>
            @foreach($loan->installmentRecords as $index => $installment)
<tr>
    <td>{{ $index + 1 }}</td>
    <td>{{ number_format(data_get($installment, 'amount', 0), 2) }}</td>
    <td>{{ ucfirst(data_get($installment, 'status', 'paid')) }}</td>
    <td>
        @if($installment->salary)
            {{ \Carbon\Carbon::parse($installment->date_paid)->translatedFormat('F Y') }}
        @else
            Not Paid
        @endif
    </td>
    <td>
        @if($installment->salary)
        {{ \Carbon\Carbon::parse($installment->salary->created_at)->format('F Y') }}
        @else
            N/A
        @endif
    </td>
</tr>
@endforeach

        </table>

    </div>

</body>
</html>
