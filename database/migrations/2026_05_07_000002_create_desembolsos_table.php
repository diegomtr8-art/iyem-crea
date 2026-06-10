<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->unique()->constrained('creditos')->cascadeOnDelete();
            $table->date('fecha_desembolso');
            $table->decimal('monto_desembolsado', 15, 2);
            $table->enum('forma_desembolso', ['Transferencia', 'Cheque', 'Efectivo', 'Deposito']);
            $table->string('banco_destino')->nullable();
            $table->string('cuenta_destino')->nullable();
            $table->string('folio_cheque')->nullable();
            $table->string('referencia')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->string('comprobante_ruta')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desembolsos');
    }
};
