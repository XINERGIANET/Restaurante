<?php

return [

    /*
     * Si un pedido manda comanda a ticketera de estación (USB en otro PC) sin IP, no usar spooler de Windows
     * del servidor: encolar en caché y la PC con QZ abre /print-bridge/worker hace pop e imprime.
     * Sin migraciones: solo Cache (archivo/Redis).
     */
    'enabled' => filter_var(env('PRINT_BRIDGE_ENABLED', true), FILTER_VALIDATE_BOOL),

    // Nombres de printers_branch (como en BD) que usan el puente cuando no hay IP
    'station_printer_names' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PRINT_BRIDGE_STATION_PRINTERS', 'BARRA2'))
    ))),

    'cache_ttl_seconds' => (int) env('PRINT_BRIDGE_CACHE_TTL', 600),

    'max_queue_length' => (int) env('PRINT_BRIDGE_MAX_JOBS', 200),

    /*
     * Token para /print-bridge/kiosk en la PC2 sin iniciar sesión (solo LAN; cadena larga aleatoria).
     * El branch_id en la URL debe coincidir con la sucursal del POS móvil (session branch_id al comandar).
     */
    'kiosk_token' => trim((string) env('PRINT_BRIDGE_KIOSK_TOKEN', '')),

];
