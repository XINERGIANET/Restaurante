<?php

return [

    /*
     * Cola en caché para comandas hacia ticketeras sin IP (USB en otra PC).
     * Los nombres de impresora que usan el puente son los mismos que QZ_SECONDARY_FIRST_PRINTER_NAMES (qz.php).
     */

    'cache_ttl_seconds' => 600,

    'lease_seconds' => (int) env('PRINT_BRIDGE_LEASE_SECONDS', 45),

    'max_queue_length' => 200,

    // Solo esta estación puede despachar las ticketeras LAN sin estación propia.
    'lan_gateway_station_names' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PRINT_BRIDGE_LAN_GATEWAY_STATIONS', 'PRINCIPAL'))
    ))),

];
