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
        // database/migrations/2026_01_01_000004_create_transacciones_table.php
Schema::create('transacciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('credito_id')->constrained();
    $table->foreignId('user_id')->constrained(); // Quién cobró
    
    $table->enum('tipo', ['Pago', 'Abono Capital', 'Condonación', 'Comisión', 'Reversión']);
    $table->decimal('monto_total', 15, 2);
    $table->date('fecha_aplicacion');
    $table->string('metodo_pago')->default('Efectivo');
    $table->text('observaciones')->nullable();
    
    $table->softDeletes(); // Para "eliminar" registros con seguridad
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
