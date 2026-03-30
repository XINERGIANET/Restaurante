<?php

namespace App\Services;

use RuntimeException;

class ThermalNetworkPrintService
{
    public function sendRaw(string $host, int $port, string $payload, int $timeoutSeconds = 4): void
    {
        $host = trim($host);
        if ($host === '' || $port <= 0) {
            throw new RuntimeException('Host o puerto de impresora no válido.');
        }

        $errno = 0;
        $errstr = '';
        $target = sprintf('tcp://%s:%d', $host, $port);
        $fp = @stream_socket_client($target, $errno, $errstr, $timeoutSeconds, STREAM_CLIENT_CONNECT);
        if ($fp === false) {
            throw new RuntimeException("No se pudo conectar a la ticketera ({$host}:{$port}): {$errstr} ({$errno})");
        }

        try {
            stream_set_timeout($fp, $timeoutSeconds);
            $written = @fwrite($fp, $payload);
            if ($written === false || $written < strlen($payload)) {
                throw new RuntimeException('Envío incompleto a la ticketera.');
            }
            fflush($fp);
        } finally {
            fclose($fp);
        }
    }
}
