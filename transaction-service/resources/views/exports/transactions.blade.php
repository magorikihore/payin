<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Transaction Ref</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Platform Charge</th>
            <th>Operator Charge</th>
            <th>Currency</th>
            <th>Status</th>
            <th>Operator</th>
            <th>Payment Method</th>
            <th>Operator Receipt</th>
            <th>Description</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $i => $tx)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $tx->transaction_ref }}</td>
            <td>{{ ucfirst($tx->type) }}</td>
            <td>{{ number_format($tx->amount, 2) }}</td>
            <td>{{ number_format($tx->platform_charge, 2) }}</td>
            <td>{{ number_format($tx->operator_charge, 2) }}</td>
            <td>{{ $tx->currency }}</td>
            <td>{{ ucfirst($tx->status) }}</td>
            <td>{{ ucfirst($tx->operator) }}</td>
            <td>{{ $tx->payment_method }}</td>
            <td>{{ $tx->operator_receipt }}</td>
            <td>{{ $tx->description }}</td>
            <td>{{ $tx->created_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
