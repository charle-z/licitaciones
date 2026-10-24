<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Documento extends Model
{
    protected $table = 'ofertas_documentos';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null;
    protected $hidden = ['archivo'];
    protected $guarded = ['id', 'licitacion_id'];
}
