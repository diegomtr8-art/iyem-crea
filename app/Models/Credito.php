<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Credito extends Model
{
    use HasFactory;

    protected $fillable = [
        'acreditado_id',
        'modalidad_id',
        'clave_contrato',
        'numero_contrato_oficial',
        'folio_solicitud',
        'monto_otorgado',
        'plazo_meses',
        'fecha_entrega',
        'fecha_contrato',
        'contrato_ruta',
        'tasa_interes_ordinario',
        'tasa_interes_moratorio',
        'estatus',
    ];

    protected $casts = [
        'fecha_entrega'          => 'date',
        'fecha_contrato'         => 'date',
        'monto_otorgado'         => 'decimal:2',
        'tasa_interes_ordinario' => 'decimal:2',
        'tasa_interes_moratorio' => 'decimal:2',
    ];

    public function acreditado()
    {
        return $this->belongsTo(Acreditado::class);
    }

    public function modalidad()
    {
        return $this->belongsTo(ModalidadCrea::class, 'modalidad_id');
    }

    public function amortizaciones()
    {
        return $this->hasMany(Amortizacion::class, 'credito_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'credito_id');
    }

    public function gestionesCobranza()
    {
        return $this->hasMany(GestionCobranza::class, 'credito_id')->orderByDesc('fecha_gestion');
    }

    public function expedienteJuridico()
    {
        return $this->hasOne(ExpedienteJuridico::class, 'credito_id');
    }

    public function desembolso(): HasOne
    {
        return $this->hasOne(Desembolso::class, 'credito_id');
    }

    public function reestructuraciones(): HasMany
    {
        return $this->hasMany(Reestructuracion::class, 'credito_id')->orderByDesc('fecha_reestructura');
    }

    public function condonaciones(): HasMany
    {
        return $this->hasMany(CondonacionFormal::class, 'credito_id')->orderByDesc('created_at');
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(AuditoriaLog::class, 'entidad_id')
            ->where('entidad', 'Credito')
            ->orderByDesc('created_at');
    }

    /**
     * Tasa moratoria efectiva. Artesanal (0% ordinaria) nunca genera mora,
     * sin importar los días de atraso.
     */
    public function tasaMoratoriaEfectiva(): float
    {
        return (float) $this->tasa_interes_ordinario > 0 ? (float) $this->tasa_interes_moratorio : 0.0;
    }

    public function diasMora(): int
    {
        $cuotaVencida = $this->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->where('fecha_vencimiento', '<', now())
            ->orderBy('fecha_vencimiento')
            ->first();

        if (!$cuotaVencida) return 0;

        return (int) now()->diffInDays($cuotaVencida->fecha_vencimiento);
    }

    public function semaforoRiesgo(): string
    {
        $dias = $this->diasMora();
        if ($dias === 0)       return 'verde';
        if ($dias <= 30)       return 'amarillo';
        if ($dias <= 60)       return 'naranja';
        if ($dias <= 90)       return 'rojo';
        return 'negro';
    }
}
