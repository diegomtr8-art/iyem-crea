<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_credito')->onDelete('cascade');
            $table->string('tipo_documento', 50); // ine_frente, ine_reverso, curp, comprobante_domicilio, foto_negocio
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->enum('estatus', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_solicitud');
    }
};
