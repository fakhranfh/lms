<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAT rate
    |--------------------------------------------------------------------------
    |
    | Expressed as a fraction of the subtotal (e.g. 0.11 = 11% PPN).
    |
    */

    'vat_rate' => (float) env('BILLING_VAT_RATE', 0.11),

    /*
    |--------------------------------------------------------------------------
    | Admin fee rate
    |--------------------------------------------------------------------------
    |
    | Payment processing/admin fee, expressed as a fraction of the subtotal
    | (e.g. 0.02 = 2%).
    |
    */

    'admin_fee_rate' => (float) env('BILLING_ADMIN_FEE_RATE', 0.02),

];
