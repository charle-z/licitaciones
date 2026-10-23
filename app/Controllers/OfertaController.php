<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Models\Actividad;
use App\Models\Oferta;
use App\Services\DocumentStorage;
use App\Services\OfertaExporter;
use App\Validation\DocumentoValidator;
use App\Validation\OfertaValidator;
use Illuminate\Database\Capsule\Manager as DB;

final class OfertaController
{
    public function __construct(private readonly DocumentStorage $storage)
    {
    }

    public function index(): void
    {
        $query = Oferta::filtrar(Request::filters());
        $total = (clone $query)->count();
        $page = Request::page();
        Response::json([
            'data' => $query->orderByDesc('id')->offset(($page - 1) * 10)->limit(10)->get(),
            'total' => $total, 'pagina' => $page, 'por_pagina' => 10,
        ]);
    }

    public function show(int $id): void
    {
        $offer = Oferta::with(['actividad', 'documentos'])->find($id);
        if ($offer === null) {
            throw new HttpException('La oferta no existe.', 404);
        }
        Response::json(['data' => $offer]);
    }

    public function create(): void
    {
        $attributes = $this->validatedAttributes(Request::body());
        $offer = DB::connection()->transaction(static function () use ($attributes) {
            $offer = Oferta::create($attributes);
            // El ID lo asigna MySQL; no se usa MAX()+1 y la fila se confirma al terminar.
            $offer->forceFill(['consecutivo' => sprintf('PO-%04d-%s', $offer->id, date('y'))])->save();
            return $offer;
        });
        Response::json(['data' => $offer], 201);
    }

    public function update(int $id): void
    {
        $input = Request::body();
        $attributes = $this->validatedAttributes($input);
        $documents = DocumentoValidator::validate($input, $_FILES);
        $storedFiles = [];
        try {
            $offer = DB::connection()->transaction(function () use ($id, $attributes, $documents, &$storedFiles) {
                $offer = Oferta::whereKey($id)->lockForUpdate()->first();
                if ($offer === null) {
                    throw new HttpException('La oferta no existe.', 404);
                }
                if (!$offer->documentos()->exists() && $documents === []) {
                    throw new HttpException('Adjunta al menos un documento para guardar la edición.', 422, ['documentos' => 'Debes agregar al menos un documento.']);
                }
                foreach ($documents as $document) {
                    $storedFilename = $this->storage->store($document);
                    $storedFiles[] = $storedFilename;
                    $offer->documentos()->create([
                        'titulo' => $document['titulo'], 'descripcion' => $document['descripcion'],
                        'archivo' => $storedFilename, 'nombre_original' => $document['nombre_original'],
                        'mime' => $document['mime'], 'tamano' => $document['tamano'],
                    ]);
                }
                $offer->fill($attributes)->save();
                return $offer->load(['actividad', 'documentos']);
            });
        } catch (\Throwable $error) {
            foreach ($storedFiles as $storedFilename) {
                $this->storage->remove($storedFilename);
            }
            throw $error;
        }
        Response::json(['data' => $offer]);
    }

    public function export(): void
    {
        $path = (new OfertaExporter())->generate(Request::filters());
        try {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="ofertas.xlsx"');
            header('Cache-Control: no-store');
            header('Content-Length: ' . filesize($path));
            readfile($path);
        } finally {
            unlink($path);
        }
    }

    private function validatedAttributes(array $input): array
    {
        $data = OfertaValidator::validate($input);
        if (!Actividad::whereKey($data['actividad_id'])->exists()) {
            throw new HttpException('Selecciona una actividad existente.', 422, ['actividad_id' => 'La actividad no existe en el catálogo.']);
        }
        return $data;
    }
}
