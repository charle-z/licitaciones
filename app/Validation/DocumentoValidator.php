<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\HttpException;
use finfo;
use JsonException;
use ZipArchive;

final class DocumentoValidator
{
    public static function validate(array $input, array $files): array
    {
        try {
            $metadata = json_decode(is_string($input['documentos'] ?? null) ? $input['documentos'] : '[]', true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException('Los datos de los documentos no son válidos.');
        }
        if (!is_array($metadata) || !array_is_list($metadata) || count($metadata) > 5) {
            throw new HttpException('Puedes adjuntar hasta cinco documentos por guardado.');
        }
        $uploads = $files['archivos'] ?? [];
        $count = isset($uploads['name']) && is_array($uploads['name']) ? count($uploads['name']) : 0;
        if ($count !== count($metadata)) {
            throw new HttpException('Los archivos no coinciden con los documentos indicados.');
        }
        $documents = [];
        foreach ($metadata as $index => $meta) {
            if (!is_array($meta)) {
                throw new HttpException('Revisa los datos del documento.');
            }
            foreach (['titulo' => 100, 'descripcion' => 200] as $field => $max) {
                if (!is_string($meta[$field] ?? null) || trim($meta[$field]) === '' || mb_strlen(trim($meta[$field])) > $max) {
                    throw new HttpException("El {$field} del documento es obligatorio y admite hasta {$max} caracteres.");
                }
            }
            $tmp = $uploads['tmp_name'][$index] ?? '';
            $name = $uploads['name'][$index] ?? '';
            $size = $uploads['size'][$index] ?? 0;
            $error = $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if (!is_string($tmp) || !is_string($name) || !is_int($size) || $error !== UPLOAD_ERR_OK || $size < 1 || $size > 10 * 1024 * 1024 || !is_uploaded_file($tmp)) {
                throw new HttpException('Cada archivo debe ser válido y pesar como máximo 10 MB.');
            }
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if ($extension === 'pdf' && $mime === 'application/pdf') {
                if (file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') {
                    throw new HttpException('El PDF no tiene un formato válido.');
                }
            } elseif ($extension === 'zip' && in_array($mime, ['application/zip', 'application/x-zip-compressed'], true)) {
                $zip = new ZipArchive();
                if ($zip->open($tmp, ZipArchive::CHECKCONS) !== true) {
                    throw new HttpException('El ZIP no tiene un formato válido.');
                }
                $zip->close();
            } else {
                throw new HttpException('Solo se permiten archivos PDF o ZIP.');
            }
            $documents[] = [
                'titulo' => trim($meta['titulo']), 'descripcion' => trim($meta['descripcion']),
                'tmp' => $tmp, 'extension' => $extension, 'mime' => $mime, 'tamano' => $size,
                'nombre_original' => mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name))) ?? 'documento.' . $extension, 0, 255),
            ];
        }
        return $documents;
    }
}
