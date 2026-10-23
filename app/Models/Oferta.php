<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Oferta extends Model
{
    protected $table = 'ofertas';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';
    protected $fillable = [
        'objeto', 'descripcion', 'moneda', 'presupuesto', 'actividad_id',
        'fecha_inicio', 'hora_inicio', 'fecha_cierre', 'hora_cierre',
    ];
    protected $casts = ['presupuesto' => 'decimal:2', 'actividad_id' => 'integer'];

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'licitacion_id')->orderBy('id');
    }

    public function scopeFiltrar(Builder $query, array $filters): Builder
    {
        foreach (['consecutivo', 'objeto', 'descripcion'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $query->where($field, 'like', '%' . addcslashes($filters[$field], '\\%_') . '%');
            }
        }
        return $query;
    }
}
