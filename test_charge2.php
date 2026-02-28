<?php
require_once '/var/www/payment/transaction-service/vendor/autoload.php';
$app = require_once '/var/www/payment/transaction-service/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$configs = App\Models\ChargeConfig::where('status','active')->get();
foreach($configs as $c) {
    echo $c->id . ' | ' . $c->name . ' | op:' . $c->operator . ' | type:' . $c->transaction_type . ' | applies:' . $c->applies_to . ' | charge_type:' . $c->charge_type . ' | val:' . $c->charge_value . ' | min:' . $c->min_amount . ' | max:' . $c->max_amount . ' | account_id:' . $c->account_id . ' | tiers:' . json_encode($c->tiers) . PHP_EOL;
}
