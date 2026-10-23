<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

final class Request
{
    public static function body(): array
    {
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 60 * 1024 * 1024) {
            throw new HttpException('La solicitud supera el tamaño permitido.', 413);
        }
        if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            try {
                $data = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new HttpException('El cuerpo JSON no es válido.', 400);
            }
            if (!is_array($data) || array_is_list($data) && $data !== []) {
                throw new HttpException('Se esperaba un objeto JSON.', 400);
            }
            return $data;
        }
        return $_POST;
    }

    public static function filters(): array
    {
        $filters = [];
        foreach (['consecutivo' => 40, 'objeto' => 150, 'descripcion' => 400] as $field => $max) {
            $value = $_GET[$field] ?? '';
            if (!is_string($value) || mb_strlen($value) > $max) {
                throw new HttpException('Revisa los filtros de búsqueda.', 422);
            }
            $filters[$field] = trim($value);
        }
        return $filters;
    }

    public static function page(): int
    {
        $page = filter_var($_GET['pagina'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
        if ($page === false) {
            throw new HttpException('La página solicitada no es válida.');
        }
        return $page;
    }
}
