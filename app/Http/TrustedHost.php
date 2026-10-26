<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class TrustedHost
{
    private const DEFAULT_HOSTS = 'localhost:8091,127.0.0.1:8091,127.0.0.1:8080';

    public static function validate(mixed $authority, string|false $configuration = false): void
    {
        $allowedHosts = array_map('trim', explode(',', $configuration === false ? self::DEFAULT_HOSTS : $configuration));
        foreach ($allowedHosts as $allowedHost) {
            if (!self::isValidAuthority($allowedHost)) {
                throw new RuntimeException('La configuración de hosts permitidos no es válida.');
            }
        }

        if (!is_string($authority) || !self::isValidAuthority($authority)
            || !in_array(strtolower($authority), array_map('strtolower', $allowedHosts), true)) {
            throw new HttpException('El host solicitado no está permitido.', 400);
        }
    }

    private static function isValidAuthority(string $authority): bool
    {
        if (strlen($authority) > 259 || !preg_match('/\A(?<host>[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*)(?::(?<port>[1-9][0-9]{0,4}))?\z/i', $authority, $parts)) {
            return false;
        }

        return strlen($parts['host']) <= 253 && (!isset($parts['port']) || (int) $parts['port'] <= 65535);
    }
}
