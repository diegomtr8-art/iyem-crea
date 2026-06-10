<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentoSolicitud extends Model
{
    protected $table = 'documentos_solicitud';

    protected $fillable = [
        'solicitud_id',
        'tipo_documento',
        'nombre_original',
        'ruta_archivo',
        'estatus',
        'observacion',
    ];

    protected $appends = ['url'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta_archivo);
    }

    public static function tiposRequeridos(): array
    {
        return [
            'ine_frente'           => 'INE / Credencial (Frente)',
            'ine_reverso'          => 'INE / Credencial (Reverso)',
            'curp'                 => 'Documento CURP oficial',
            'comprobante_domicilio'=> 'Comprobante de Domicilio',
            'foto_negocio'         => 'Fotografía del Negocio o Proyecto',
        ];
    }
}
