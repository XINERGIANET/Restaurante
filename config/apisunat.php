<?php

return [
    'url' => env('APISUNAT_URL', 'https://back.apisunat.com'),
    'id' => env('APISUNAT_ID'),
    'token' => [
        'prod' => env('APISUNAT_TOKEN_PROD'),
    ],
    'series' => [
        'boleta' => env('APISUNAT_SERIES_BOLETA', 'B001'),
        'factura' => env('APISUNAT_SERIES_FACTURA', 'F001'),
    ],
    'timeouts' => [
        'connect' => (int) env('APISUNAT_CONNECT_TIMEOUT', 3),
        'correlative' => (int) env('APISUNAT_CORRELATIVE_TIMEOUT', 6),
        'emit' => (int) env('APISUNAT_EMIT_TIMEOUT', 15),
        'lookup' => (int) env('APISUNAT_LOOKUP_TIMEOUT', 6),
    ],
    // La consulta getById no es necesaria para terminar el cobro: los enlaces
    // PDF se construyen con el documentId devuelto por sendBill.
    'lookup_after_emit' => (bool) env('APISUNAT_LOOKUP_AFTER_EMIT', false),
];
