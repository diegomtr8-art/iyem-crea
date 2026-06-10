<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acreditado_id')->constrained('acreditados')->onDelete('cascade');
            $table->foreignId('modalidad_id')->constrained('modalidad_creas');

            $table->string('clave_contrato')->unique();
            $table->string('folio_solicitud')->nullable();
            $table->decimal('monto_otorgado', 15, 2);
            $table->integer('plazo_meses');
            $table->date('fecha_entrega');

            $table->decimal('tasa_interes_ordinario', 5, 2)->default(0);
            $table->decimal('tasa_interes_moratorio', 10, 2)->default(0);

            $table->string('estatus')->default('Activo'); // Activo, Moroso, Liquidado, Cancelado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creditos');
    }
};
