<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\HttpException;
use RuntimeException;

final class DocumentStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    public function store(array $document): string
    {
        $name = bin2hex(random_bytes(16)) . '.' . $document['extension'];
        if (!move_uploaded_file($document['tmp'], $this->directory . '/' . $name)) {
            throw new RuntimeException('No se pudo almacenar el documento.');
        }
        chmod($this->directory . '/' . $name, 0600);
        return $name;
    }

    public function path(string $name): string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(?:pdf|zip)$/D', $name)) {
            throw new HttpException('El documento no está disponible.', 404);
        }
        $path = $this->directory . '/' . $name;
        if (!is_file($path) || is_link($path)) {
            throw new HttpException('El documento no está disponible.', 404);
        }
        return $path;
    }

    public function remove(string $name): void
    {
        if (!preg_match('/^[a-f0-9]{32}\.(?:pdf|zip)$/D', $name)) {
            return;
        }
        $path = $this->directory . '/' . $name;
        if (is_file($path) && !is_link($path) && !unlink($path)) {
            error_log('No se pudo retirar un archivo de una operación revertida.');
        }
    }
}
