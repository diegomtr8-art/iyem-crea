<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionCobranza extends Model
{
    protected $table = 'gestiones_cobranza';

    protected $fillable = [
        'credito_id', 'acreditado_id', 'usuario_id',
        'tipo_gestion', 'descripcion', 'resultado',
        'fecha_gestion', 'fecha_compromiso_pago', 'monto_comprometido',
    ];

    protected $casts = [
        'fecha_gestion'         => 'date',
        'fecha_compromiso_pago' => 'date',
        'monto_comprometido'    => 'decimal:2',
    ];

    public function credito()    { return $this->belongsTo(Credito::class); }
    public function acreditado() { return $this->belongsTo(Acreditado::class); }
    public function usuario()    { return $this->belongsTo(User::class, 'usuario_id'); }
}
