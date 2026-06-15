<?php

return [
    'tenant_portal' => env('OKGV_MODULE_TENANT_PORTAL', true),
    'meters' => env('OKGV_MODULE_METERS', true),
    'billing' => env('OKGV_MODULE_BILLING', true),
    'work_hours' => env('OKGV_MODULE_WORK_HOURS', true),
    'work_events' => env('OKGV_MODULE_WORK_EVENTS', true),
    'sepa' => env('OKGV_MODULE_SEPA', true),
    'dunning' => env('OKGV_MODULE_DUNNING', true),
    'documents' => env('OKGV_MODULE_DOCUMENTS', true),
    'communication' => env('OKGV_MODULE_COMMUNICATION', true),
    'waiting_list' => env('OKGV_MODULE_WAITING_LIST', true),
    'inventory' => env('OKGV_MODULE_INVENTORY', true),
];
