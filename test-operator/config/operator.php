<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Operator Credentials
    |--------------------------------------------------------------------------
    |
    | These must match what is configured in the Payin admin panel for this
    | test operator. Payin uses these to generate spPassword for requests.
    |
    */
    'sp_id'         => env('OPERATOR_SP_ID', '600100'),
    'merchant_code' => env('OPERATOR_MERCHANT_CODE', '6001001'),
    'sp_password'   => env('OPERATOR_SP_PASSWORD', 'TestOperator@2025'),
    'name'          => env('OPERATOR_NAME', 'Test Operator'),
    'code'          => env('OPERATOR_CODE', 'testoperator'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Callback Settings
    |--------------------------------------------------------------------------
    |
    | When auto_callback is true, the simulator automatically sends a callback
    | back to Payin after receiving a request, simulating customer action.
    |
    | auto_callback_delay: seconds to wait before sending callback (simulates
    |                      customer interaction time)
    | auto_callback_result: 'success' or 'failed' — default behavior
    |
    */
    'auto_callback'        => env('AUTO_CALLBACK', true),
    'auto_callback_delay'  => env('AUTO_CALLBACK_DELAY', 3),
    'auto_callback_result' => env('AUTO_CALLBACK_RESULT', 'success'),
];
