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
       // database/migrations/2026_01_01_000003_create_amortizaciones_table.php
Schema::create('amortizaciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('credito_id')->constrained()->onDelete('cascade');
    $table->integer('numero_cuota'); 
    $table->date('fecha_vencimiento');
    
    // Proyectado (Plan original)
    $table->decimal('saldo_insoluto', 15, 2);
    $table->decimal('capital_esperado', 15, 2);
    $table->decimal('interes_ordinario_esperado', 15, 2);
    $table->decimal('cuota_fija', 15, 2); // Total Pago Proyectado
    
    // Real (Lo que el cliente ha pagado de esta cuota)
    $table->decimal('capital_pagado', 15, 2)->default(0);
    $table->decimal('interes_ordinario_pagado', 15, 2)->default(0);
    $table->decimal('interes_moratorio_pagado', 15, 2)->default(0);
    $table->decimal('comision_pagada', 15, 2)->default(0);
    
    // Cálculos Dinámicos
    $table->decimal('moratorio_acumulado', 15, 2)->default(0); // Se actualiza por Job o al ver el Show
    $table->decimal('pago_restante', 15, 2); // Cuota + Moras - Pagado
    
    $table->enum('estado', ['Pendiente', 'Parcial', 'Pagado', 'Condonado'])->default('Pendiente');
    $table->date('fecha_ultimo_pago')->nullable(); // Fecha Pago Cliente en tu tabla
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amortizaciones');
    }
};
