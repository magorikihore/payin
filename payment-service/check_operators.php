<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ops = \App\Models\Operator::all();
foreach ($ops as $op) {
    echo "ID: {$op->id}\n";
    echo "  Name: {$op->name}\n";
    echo "  Code: {$op->code}\n";
    echo "  Gateway: {$op->gateway_type}\n";
    echo "  API URL: {$op->api_url}\n";
    echo "  SP ID: {$op->sp_id}\n";
    echo "  Merchant Code: {$op->merchant_code}\n";
    echo "  SP Password set: " . ($op->sp_password ? 'YES ('.strlen($op->sp_password).' chars)' : 'NO') . "\n";
    echo "  Collection Path: {$op->collection_path}\n";
    echo "  Disbursement Path: {$op->disbursement_path}\n";
    echo "  Callback URL: {$op->callback_url}\n";
    echo "  Status: {$op->status}\n";
    echo "  Extra Config: " . json_encode($op->extra_config) . "\n";
    echo "---\n";
}
