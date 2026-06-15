<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies
    |--------------------------------------------------------------------------
    |
    | Configure the IP addresses or CIDR ranges of reverse proxies that are
    | allowed to supply X-Forwarded headers. Separate multiple values with
    | commas. Use "*" only when direct access to the application server is
    | prevented by the network.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),
];
