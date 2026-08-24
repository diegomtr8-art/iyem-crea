<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvalSolicitud extends Model
{
    protected $table = 'avales_solicitud';

    protected $fillable = [
        'solicitud_id',
        'nombre_completo',
        'parentesco',
        'correo',
        'sexo',
        'rfc',
        'curp',
        'telefono_celular',
        'telefono_fijo',
        'edad',
        'fecha_nacimiento',
        'municipio_nacimiento',
        'municipio_residencia',
        'domicilio',
        'colonia',
        'cp',
        'domicilio_propio',
        'renta_mensual',
        'lugar_laboral',
        'antiguedad_laboral',
        'ocupacion',
        'fecha_inicio_actividades',
        'dependientes_economicos',
        'estado_civil',
        'regimen_matrimonial',
        'nombre_conyuge',
        'bienes_inmuebles',
        'bienes_muebles',
        'hipotecas_creditos',
        'otras_deudas',
        'ingresos',
        'egresos',
        'referencias_personales',
    ];

    protected $casts = [
        'fecha_nacimiento'       => 'date',
        'fecha_inicio_actividades' => 'date',
        'domicilio_propio'       => 'boolean',
        'renta_mensual'          => 'decimal:2',
        'bienes_inmuebles'       => 'array',
        'bienes_muebles'         => 'array',
        'hipotecas_creditos'     => 'array',
        'otras_deudas'           => 'array',
        'ingresos'               => 'array',
        'egresos'                => 'array',
        'referencias_personales' => 'array',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }
}
