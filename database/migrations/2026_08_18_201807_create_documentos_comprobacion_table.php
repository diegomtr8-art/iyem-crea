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
        Schema::create('documentos_comprobacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobacion_id')->constrained('comprobaciones_uso')->onDelete('cascade');
            $table->string('tipo');
            $table->string('descripcion')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->string('proveedor')->nullable();
            $table->string('ruta_archivo');
            $table->string('nombre_original');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_comprobacion');
    }
};
