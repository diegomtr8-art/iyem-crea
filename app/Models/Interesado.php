<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interesado extends Model
{
    protected $fillable = [
        'medio_ingreso', 'nombre_ciudadano', 'empresa', 'correo_electronico',
        'telefono', 'sexo', 'municipio', 'fecha_nacimiento', 'mayahablante',
        'discapacidad', 'giro_comercial', 'alta_sat', 'destino_credito',
        'modalidad', 'atendio', 'seguimiento', 'estatus'
    ];
}
