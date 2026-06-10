<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->onDelete('cascade');
            $table->foreignId('acreditado_id')->constrained('acreditados')->onDelete('cascade');
            
            $table->string('folio')->unique(); // Folio de ticket
            $table->decimal('monto_recibido', 15, 2);
            $table->decimal('aplicado_mora', 15, 2)->default(0);
            $table->decimal('aplicado_ordinario', 15, 2)->default(0);
            $table->decimal('aplicado_capital', 15, 2)->default(0);
            
            // Guardamos el desglose detallado como JSON para auditoría rápida
            $table->json('cuotas_cubiertas')->nullable();
            
            $table->string('forma_pago'); // Efectivo, Transferencia, etc.
            $table->string('referencia')->nullable();
            $table->date('fecha_pago');
            $table->text('observaciones')->nullable();
            
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};