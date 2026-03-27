<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class QzTraySigningService
{
    public function isConfigured(): bool
    {
        try {
            return $this->readConfiguredFile('qz.private_key_path', 'clave privada') !== ''
                && $this->readConfiguredFile('qz.certificate_path', 'certificado') !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public function certificateContents(): ?string
    {
        try {
            return $this->readConfiguredFile('qz.certificate_path', 'certificado');
        } catch (\Throwable) {
            return null;
        }
    }

    public function sign(string $request): ?string
    {
        try {
            $pem = $this->readConfiguredFile('qz.private_key_path', 'clave privada');
        } catch (\Throwable) {
            return null;
        }

        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            return null;
        }

        $signature = '';
        $ok = openssl_sign($request, $signature, $key, $this->opensslAlgorithmFromConfig());

        if (! $ok) {
            return null;
        }

        return base64_encode($signature);
    }

    private function readConfiguredFile(string $configKey, string $label): string
    {
        $configured = (string) config($configKey, '');

        if (trim($configured) === '') {
            throw new \RuntimeException("Archivo de {$label} QZ no configurado en {$configKey}.");
        }

        // Soporta caso accidental donde se pegue el contenido PEM/base64 en el .env.
        if (str_contains($configured, 'BEGIN ') || strlen($configured) > 500) {
            return trim($configured);
        }

        $path = $this->resolvePath($configured);

        if (! File::exists($path)) {
            throw new \RuntimeException("Archivo de {$label} QZ no encontrado en {$path}.");
        }

        $content = (string) File::get($path);
        if (trim($content) === '') {
            throw new \RuntimeException("Archivo de {$label} QZ vacío.");
        }

        return $content;
    }

    private function resolvePath(string $path): string
    {
        if (
            preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) ||
            str_starts_with($path, '/') ||
            str_starts_with($path, '\\\\')
        ) {
            return $path;
        }

        return base_path($path);
    }

    private function opensslAlgorithmFromConfig(): int
    {
        $algo = strtoupper((string) config('qz.signature_algorithm', 'SHA512'));
        if ($algo === 'SHA512') return OPENSSL_ALGO_SHA512;
        if ($algo === 'SHA384') return OPENSSL_ALGO_SHA384;
        if ($algo === 'SHA256') return OPENSSL_ALGO_SHA256;
        return OPENSSL_ALGO_SHA1;
    }
}
