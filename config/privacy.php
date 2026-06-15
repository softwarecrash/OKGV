<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum retention period
    |--------------------------------------------------------------------------
    |
    | This is a conservative technical default. The responsible association
    | must verify which statutory or contractual periods apply to its records.
    |
    */
    'retention_years' => max(1, (int) env('OKGV_PRIVACY_RETENTION_YEARS', 10)),
];
