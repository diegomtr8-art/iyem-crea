<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteJuridico extends Model
{
    protected $table = 'expedientes_juridicos';

    protected $fillable = [
        'credito_id', 'acreditado_id', 'usuario_id',
        'fecha_envio_juridico', 'estatus',
        'tribunal', 'juzgado', 'numero_expediente_judicial',
        'monto_reclamado', 'observaciones',
    ];

    protected $casts = [
        'fecha_envio_juridico' => 'date',
        'monto_reclamado'      => 'decimal:2',
    ];

    public function credito()    { return $this->belongsTo(Credito::class); }
    public function acreditado() { return $this->belongsTo(Acreditado::class); }
    public function usuario()    { return $this->belongsTo(User::class, 'usuario_id'); }
}
