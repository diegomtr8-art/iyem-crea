<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comprobaciones_uso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->onDelete('cascade');
            $table->foreignId('solicitud_id')->constrained('solicitudes_credito')->onDelete('cascade');
            $table->foreignId('acreditado_id')->constrained('acreditados')->onDelete('cascade');
            $table->date('fecha_desembolso');
            $table->date('fecha_limite_comprobacion');
            $table->enum('estatus', ['Pendiente', 'En_Revision', 'Aprobada', 'Rechazada'])->default('Pendiente');
            $table->text('observaciones_acreditado')->nullable();
            $table->text('observaciones_operativo')->nullable();
            $table->date('fecha_revision')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->decimal('monto_comprobado', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobaciones_uso');
    }
};
