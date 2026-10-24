<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Models\Documento;
use App\Services\DocumentStorage;

final class DocumentoController
{
    public function __construct(private readonly DocumentStorage $storage)
    {
    }

    public function download(int $id): void
    {
        $document = Documento::find($id);
        if ($document === null) {
            throw new HttpException('El documento no existe.', 404);
        }
        $path = $this->storage->path($document->getRawOriginal('archivo'));
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"documento.{$this->extension($path)}\"; filename*=UTF-8''" . rawurlencode($document->nombre_original));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
    }

    private function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
}
