<?php

return [

    'enabled' => env('QZ_TRAY_ENABLED', true),

    'private_key_path' => env('QZ_PRIVATE_KEY_PATH') ?: storage_path('app/qz/private-key.pem'),

    'certificate_path' => env('QZ_CERTIFICATE_PATH') ?: storage_path('app/qz/digital-certificate.txt'),

    'printer_name' => env('QZ_PRINTER_NAME', 'BARRA'),

    // Algoritmo de firma que validará QZ (debe coincidir con qz.security.setSignatureAlgorithm()).
    // Valores: SHA1 | SHA256 | SHA384 | SHA512
    'signature_algorithm' => env('QZ_SIGNATURE_ALGORITHM', 'SHA512'),

    // raw | pixel | auto (auto = intenta raw y cae a pixel en el frontend)
    'print_mode' => env('QZ_PRINT_MODE', 'auto'),

    // Lista blanca opcional de orígenes. Ej: "https://xinergia.net,https://restaurante.test"
    // Si está vacío, no se valida Origin.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('QZ_ALLOWED_ORIGINS', (string) env('APP_URL', '')))
    ))),

    // Si true, en producción exige Origin válido (si se configuró allowed_origins).
    'production_lock' => env('QZ_PRODUCTION_LOCK', false),

];
