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
        // database/migrations/2026_01_01_000005_create_transaccion_detalles_table.php
Schema::create('transaccion_detalles', function (Blueprint $table) {
    $table->id();
    
    // Cambia la línea de transaccion_id por esta:
    $table->foreignId('transaccion_id')
          ->constrained('transacciones') // <--- Aquí le decimos que la tabla se llama 'transacciones'
          ->onDelete('cascade');
          
    $table->foreignId('amortizacion_id')
          ->constrained('amortizaciones'); // <--- Por seguridad haz lo mismo aquí
    
    $table->decimal('monto_capital', 15, 2)->default(0);
    $table->decimal('monto_interes', 15, 2)->default(0);
    $table->decimal('monto_moratorio', 15, 2)->default(0);
    $table->decimal('monto_comision', 15, 2)->default(0);
    
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaccion_detalles');
    }
};
