<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reestructuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->date('fecha_reestructura');
            $table->enum('motivo', [
                'Dificultad_Economica',
                'Desastre_Natural',
                'Pandemia',
                'Cambio_Actividad',
                'Otro',
            ]);
            $table->decimal('saldo_al_momento', 15, 2);
            $table->decimal('mora_condonada', 15, 2)->default(0);
            $table->decimal('interes_condonado', 15, 2)->default(0);
            $table->integer('nuevo_plazo_meses');
            $table->decimal('nueva_tasa_interes', 5, 2)->default(0);
            $table->date('nueva_fecha_inicio_pagos');
            $table->foreignId('autorizado_por')->constrained('users');
            $table->string('numero_resolutivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('credito_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reestructuraciones');
    }
};
