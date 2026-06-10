<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones_cobranza', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->foreignId('acreditado_id')->constrained('acreditados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->enum('tipo_gestion', ['Llamada', 'Visita', 'WhatsApp', 'Email', 'Carta', 'Acuerdo', 'Otro']);
            $table->text('descripcion');
            $table->enum('resultado', ['Contactado', 'No_Contactado', 'Promesa_Pago', 'Sin_Respuesta', 'Acuerdo_Alcanzado', 'Otro']);
            $table->date('fecha_gestion');
            $table->date('fecha_compromiso_pago')->nullable();
            $table->decimal('monto_comprometido', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones_cobranza');
    }
};
