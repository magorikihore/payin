<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionExportController extends Controller
{
    /**
     * Build the filtered query based on request parameters.
     */
    private function buildQuery(Request $request, bool $isAdmin = false)
    {
        $user = $request->user();
        $query = Transaction::query();

        if (!$isAdmin) {
            $accountId = $user->account_id ?? null;
            if ($accountId) {
                $query->where('account_id', $accountId);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('operator_receipt', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }
        if ($isAdmin && $request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * User: Download transactions as Excel.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'status'    => 'nullable|string|in:pending,completed,failed,cancelled,reversed',
            'type'      => 'nullable|string|in:collection,disbursement,topup,settlement',
            'operator'  => 'nullable|string',
            'search'    => 'nullable|string|max:100',
        ]);

        $query = $this->buildQuery($request);
        $filename = 'transactions_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new TransactionsExport($query), $filename);
    }

    /**
     * User: Download transactions as PDF.
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'status'    => 'nullable|string|in:pending,completed,failed,cancelled,reversed',
            'type'      => 'nullable|string|in:collection,disbursement,topup,settlement',
            'operator'  => 'nullable|string',
            'search'    => 'nullable|string|max:100',
        ]);

        $query = $this->buildQuery($request);
        $transactions = $query->get();

        $pdf = Pdf::loadView('exports.transactions-pdf', [
            'transactions' => $transactions,
            'dateFrom'     => $request->date_from,
            'dateTo'       => $request->date_to,
        ])->setPaper('a4', 'landscape');

        $filename = 'transactions_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Admin: Download all transactions as Excel.
     */
    public function adminExportExcel(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'status'     => 'nullable|string|in:pending,completed,failed,cancelled,reversed',
            'type'       => 'nullable|string|in:collection,disbursement,topup,settlement',
            'operator'   => 'nullable|string',
            'account_id' => 'nullable|integer',
            'search'     => 'nullable|string|max:100',
        ]);

        $query = $this->buildQuery($request, true);
        $filename = 'all_transactions_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new TransactionsExport($query), $filename);
    }

    /**
     * Admin: Download all transactions as PDF.
     */
    public function adminExportPdf(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'status'     => 'nullable|string|in:pending,completed,failed,cancelled,reversed',
            'type'       => 'nullable|string|in:collection,disbursement,topup,settlement',
            'operator'   => 'nullable|string',
            'account_id' => 'nullable|integer',
            'search'     => 'nullable|string|max:100',
        ]);

        $query = $this->buildQuery($request, true);
        $transactions = $query->get();

        $pdf = Pdf::loadView('exports.transactions-pdf', [
            'transactions' => $transactions,
            'dateFrom'     => $request->date_from,
            'dateTo'       => $request->date_to,
        ])->setPaper('a4', 'landscape');

        $filename = 'all_transactions_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
