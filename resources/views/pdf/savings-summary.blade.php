<!DOCTYPE html>
<html>
<head>
    <title>Savings Summary</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details th { text-align: left; padding-right: 20px; }
        .transaction-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .transaction-table th, .transaction-table td { border: 1px solid #ddd; padding: 8px; }
        .transaction-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Employee Savings Summary</h2>
        <h3>{{ $plan->company->company_name ?? 'N/A' }}</h3>
    </div>

    <h4>Employee Details</h4>
    <table class="details">
        <tr>
            <th>Name:</th>
            <td>{{ $plan->employee->first_name }} {{ $plan->employee->last_name }}</td>
        </tr>
        <tr>
            <th>ID:</th>
            <td>{{ $plan->employee->employee_id }}</td>
        </tr>
        <tr>
            <th>Plan Status:</th>
            <td>{{ ucfirst($plan->status) }}</td>
        </tr>
    </table>

    <hr>

    <h4>Savings Overview</h4>
    <table class="details">
        <tr>
            <th>Total Contributions Made:</th>
            <td>{{ $contributionCount }}</td>
        </tr>
        <tr>
            <th>Current Total Saved:</th>
            <td>KES {{ number_format($totalAmount, 2) }}</td>
        </tr>
    </table>

    <hr>

    <h4>Transaction History (Ledger)</h4>
    @if($plan->savingsTransactions->count() > 0)
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->savingsTransactions as $index => $transaction)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                        <td>KES {{ number_format($transaction->amount, 2) }}</td>
                        <td>{{ $transaction->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No savings contributions recorded yet.</p>
    @endif

    <div style="margin-top: 50px; text-align: right;">
        Report Generated: {{ now()->format('Y-m-d H:i:s') }}
    </div>

</body>
</html>
