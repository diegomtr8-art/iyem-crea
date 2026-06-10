<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModalidadCrea extends Model
{
    use HasFactory;

    // Laravel a veces busca la tabla como "modalidad_creas", 
    // especificamos el nombre exacto por seguridad.
    protected $table = 'modalidad_creas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tasa_interes',
        'tasa_moratoria',
        'monto_minimo',
        'monto_maximo',
        'plazo_min_meses',
        'plazo_max_meses',
        'requiere_alta_sat',
        'requiere_antiguedad',
        'documentos_requeridos',
        'municipios_elegibles',
        'activo',
    ];

    protected $casts = [
        'tasa_interes'          => 'decimal:2',
        'tasa_moratoria'        => 'decimal:2',
        'monto_minimo'          => 'decimal:2',
        'monto_maximo'          => 'decimal:2',
        'requiere_alta_sat'     => 'boolean',
        'requiere_antiguedad'   => 'boolean',
        'activo'                => 'boolean',
        'documentos_requeridos' => 'array',
    ];

    /**
     * Relación: Una modalidad puede estar presente en muchos créditos.
     */
    public function creditos()
    {
        return $this->hasMany(Credito::class, 'modalidad_id');
    }
}