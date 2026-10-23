<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Actividad extends Model
{
    protected $table = 'actividades';
    public $timestamps = false;
    protected $guarded = ['id'];
}
