<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Acreditado extends Model
{
    use HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     * Estos corresponden a la estructura que definimos en la migración.
     */
    protected $fillable = [
        'nombre_completo',
        'rfc',
        'curp',
        'municipio',
        'sexo',
        'direccion_fiscal',
        'correo',
        'clave_personalizada',
        'regimen',
       
    ];

    /**
     * Relación: Un Acreditado tiene muchos Créditos.
     * Esto permite hacer: $acreditado->creditos()->create([...])
     */
    public function creditos()
    {
        return $this->hasMany(Credito::class);
    }

    public function solicitud()
    {
        return $this->hasOne(SolicitudCredito::class);
    }
}