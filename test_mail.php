<?php
require "/var/www/payment/auth-service/vendor/autoload.php";
$app = require_once "/var/www/payment/auth-service/bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set notification emails to magorikihore@gmail.com
\App\Models\AdminSetting::setNotificationEmails(["magorikihore@gmail.com"]);
$emails = \App\Models\AdminSetting::getNotificationEmails();
echo "Notification emails set to: " . json_encode($emails) . "\n";

// Send test notification
try {
    \Illuminate\Support\Facades\Mail::raw(
        "This is a test notification from Payin.\n\nYou will now receive admin notifications (settlement requests, transfer requests, etc.) at this email address.\n\nTime: " . now(),
        function($msg) {
            $msg->to("magorikihore@gmail.com")->subject("Payin Admin Notification Test - Confirmed Working");
        }
    );
    echo "Test notification sent to magorikihore@gmail.com - check your inbox!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
