<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condonaciones_formales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->enum('tipo', [
                'Parcial_Capital',
                'Parcial_Intereses',
                'Parcial_Mora',
                'Total',
            ]);
            $table->decimal('monto_condonado_capital', 15, 2)->default(0);
            $table->decimal('monto_condonado_intereses', 15, 2)->default(0);
            $table->decimal('monto_condonado_mora', 15, 2)->default(0);
            $table->text('motivo');
            $table->string('numero_acuerdo')->nullable();
            $table->foreignId('autorizado_por')->constrained('users');
            $table->date('fecha_autorizacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('credito_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condonaciones_formales');
    }
};
