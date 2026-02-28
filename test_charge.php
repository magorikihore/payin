<?php
require_once '/var/www/payment/transaction-service/vendor/autoload.php';
$app = require_once '/var/www/payment/transaction-service/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$result = App\Models\ChargeConfig::calculateCharges(10000, 'M-Pesa', 'disbursement');
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
$configs = App\Models\ChargeConfig::where('status','active')->get();
echo 'Total active configs: ' . $configs->count() . PHP_EOL;
foreach($configs as $c) { echo $c->name . ' | op:' . $c->operator . ' | type:' . $c->transaction_type . ' | applies:' . $c->applies_to . ' | charge_type:' . $c->charge_type . ' | val:' . $c->charge_value . PHP_EOL; }
