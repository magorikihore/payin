<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncTransactions extends Command
{
    protected $signature = 'transactions:sync';
    protected $description = 'Sync completed payment requests to the transaction service';

    public function handle()
    {
        $serviceKey = config('services.internal_service_key');
        $txnServiceUrl = config('services.transaction_service.url');

        $completed = PaymentRequest::where('status', 'completed')->get();

        $this->info("Found {$completed->count()} completed payment requests to sync.");

        $synced = 0;
        $failed = 0;

        foreach ($completed as $pr) {
            try {
                $response = Http::withHeaders(['X-Service-Key' => $serviceKey])
                    ->post("{$txnServiceUrl}/api/internal/transactions", [
                        'account_id'       => $pr->account_id,
                        'transaction_ref'  => $pr->request_ref,
                        'amount'           => $pr->amount,
                        'type'             => $pr->type,
                        'operator'         => $pr->operator_name,
                        'status'           => 'completed',
                        'platform_charge'  => $pr->platform_charge ?? 0,
                        'operator_charge'  => $pr->operator_charge ?? 0,
                        'currency'         => $pr->currency ?? 'TZS',
                        'description'      => $pr->description ?? ($pr->type === 'collection' ? 'USSD Collection' : 'Disbursement'),
                        'payment_method'   => 'mobile_money',
                        'operator_receipt' => $pr->operator_ref,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $pr->update(['transaction_id' => $data['transaction']['id'] ?? null]);
                    $synced++;
                    $this->line("  ✓ {$pr->request_ref} ({$pr->type}) — synced");
                } else {
                    $failed++;
                    $this->error("  ✗ {$pr->request_ref} — " . $response->body());
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ {$pr->request_ref} — " . $e->getMessage());
            }
        }

        $this->info("Sync complete: {$synced} synced, {$failed} failed.");
    }
}
