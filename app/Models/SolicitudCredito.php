<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudCredito extends Model
{
    protected $table = 'solicitudes_credito';

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'curp',
        'rfc',
        'fecha_nacimiento',
        'sexo',
        'mayahablante',
        'discapacidad',
        'municipio',
        'direccion',
        'telefono',
        'correo',
        'modalidad_id',
        'tipo_persona',
        'giro_comercial',
        'destino_credito',
        'descripcion_negocio',
        'monto_solicitado',
        'plazo_meses',
        'tipo_garantia',
        'alta_sat',
        'estatus',
        'observaciones',
        'asignado_a',
        'fecha_asignacion',
        'motivo_rechazo',
        'acreditado_id',
        'credito_id',
        'datos_wizard',
        'formatos_zip_ruta',
    ];

    protected $casts = [
        'fecha_nacimiento'  => 'date',
        'fecha_asignacion'  => 'datetime',
        'mayahablante'      => 'boolean',
        'discapacidad'      => 'boolean',
        'alta_sat'          => 'boolean',
        'monto_solicitado'  => 'decimal:2',
        'datos_wizard'      => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ModalidadCrea::class, 'modalidad_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoSolicitud::class, 'solicitud_id');
    }

    public function acreditado(): BelongsTo
    {
        return $this->belongsTo(Acreditado::class);
    }

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }

    public function asignadoA(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function estatusHistorial(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolicitudEstatusHistorial::class, 'solicitud_id')->orderBy('created_at');
    }

    public function analisis(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AnalisisCredito::class, 'solicitud_id');
    }

    public function aval(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AvalSolicitud::class, 'solicitud_id');
    }

    public function tieneDocumentosRechazados(): bool
    {
        return $this->documentos()->where('estatus', 'Rechazado')->exists();
    }

    public function documentosPorTipo(): array
    {
        return $this->documentos->keyBy('tipo_documento')->toArray();
    }
}
