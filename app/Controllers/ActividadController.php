<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Response;
use App\Models\Actividad;

final class ActividadController
{
    public function index(): void
    {
        $search = $_GET['q'] ?? '';
        if (!is_string($search) || mb_strlen($search) > 100) {
            throw new HttpException('La búsqueda de actividades no es válida.');
        }
        $search = trim($search);
        $query = Actividad::query();
        if ($search !== '') {
            $pattern = '%' . addcslashes($search, '\\%_') . '%';
            $query->where(static fn ($matchingActivities) => $matchingActivities->where('producto', 'like', $pattern)->orWhere('codigo_producto', 'like', $pattern));
        }
        Response::json(['data' => $query->orderBy('codigo_producto')->limit(30)->get()]);
    }
}
