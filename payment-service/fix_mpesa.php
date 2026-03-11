<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Clear M-Pesa collection/disbursement paths to match Airtel (which has no paths and works)
DB::table('operators')->where('code', 'mpesa')->update([
    'collection_path' => null,
    'disbursement_path' => null,
]);

echo "M-Pesa paths cleared.\n\nCurrent operator config:\n";
foreach (DB::table('operators')->get() as $o) {
    echo sprintf(
        "%s | sp_id=%s | merchant=%s | coll=%s | disb=%s\n",
        $o->code,
        $o->sp_id,
        $o->merchant_code,
        $o->collection_path ?? 'NULL',
        $o->disbursement_path ?? 'NULL'
    );
}
