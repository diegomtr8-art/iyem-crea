<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes_credito')->cascadeOnDelete();
            $table->foreignId('analista_id')->constrained('users');
            $table->date('fecha_analisis');

            // Capacidad de pago
            $table->decimal('ingresos_mensuales', 12, 2)->nullable();
            $table->decimal('gastos_mensuales', 12, 2)->nullable();
            $table->decimal('capacidad_pago', 12, 2)->nullable();
            $table->decimal('relacion_deuda_ingreso', 5, 2)->nullable();

            // Checklist de criterios IYEM
            $table->boolean('curp_valida')->default(false);
            $table->boolean('rfc_activo')->default(false);
            $table->boolean('municipio_elegible')->default(false);
            $table->boolean('giro_elegible')->default(false);
            $table->boolean('beneficiario_duplicado')->default(false);
            $table->boolean('alta_sat_verificada')->default(false);
            $table->boolean('documentos_completos')->default(false);
            $table->boolean('referencias_verificadas')->default(false);

            // Evaluación cualitativa
            $table->integer('antiguedad_negocio_meses')->nullable();
            $table->integer('score_cualitativo')->nullable();
            $table->text('observaciones_analisis')->nullable();

            // Dictamen
            $table->enum('recomendacion', ['Aprobar', 'Rechazar', 'Modificar_Monto'])->nullable();
            $table->decimal('monto_recomendado', 12, 2)->nullable();
            $table->string('motivo_rechazo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_credito');
    }
};
