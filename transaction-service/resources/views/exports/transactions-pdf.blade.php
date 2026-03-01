<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 11px;
            color: #666;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background-color: #2d3748;
            color: #fff;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .summary {
            margin-top: 15px;
            font-size: 11px;
        }
        .summary td {
            border: none;
            padding: 3px 6px;
        }
        .text-right {
            text-align: right;
        }
        .status-completed { color: #38a169; }
        .status-pending { color: #d69e2e; }
        .status-failed { color: #e53e3e; }
        .status-cancelled { color: #a0aec0; }
        .status-reversed { color: #805ad5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Transactions Report</h1>
        <p>
            @if($dateFrom && $dateTo)
                Period: {{ $dateFrom }} to {{ $dateTo }}
            @elseif($dateFrom)
                From: {{ $dateFrom }}
            @elseif($dateTo)
                Up to: {{ $dateTo }}
            @else
                All Transactions
            @endif
            &mdash; Generated: {{ now()->format('Y-m-d H:i:s') }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Ref</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Charges</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Operator</th>
                <th>Receipt</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $i => $tx)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $tx->transaction_ref }}</td>
                <td>{{ ucfirst($tx->type) }}</td>
                <td class="text-right">{{ number_format($tx->amount, 2) }}</td>
                <td class="text-right">{{ number_format($tx->platform_charge + $tx->operator_charge, 2) }}</td>
                <td>{{ $tx->currency }}</td>
                <td class="status-{{ $tx->status }}">{{ ucfirst($tx->status) }}</td>
                <td>{{ ucfirst($tx->operator) }}</td>
                <td>{{ $tx->operator_receipt }}</td>
                <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center;">No transactions found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($transactions->count())
    <table class="summary">
        <tr>
            <td><strong>Total Transactions:</strong></td>
            <td>{{ $transactions->count() }}</td>
            <td><strong>Total Amount:</strong></td>
            <td>{{ number_format($transactions->sum('amount'), 2) }}</td>
            <td><strong>Total Charges:</strong></td>
            <td>{{ number_format($transactions->sum('platform_charge') + $transactions->sum('operator_charge'), 2) }}</td>
        </tr>
    </table>
    @endif
</body>
</html>
