<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->date('fecha_contrato')->nullable()->after('fecha_entrega');
            $table->string('numero_contrato_oficial')->nullable()->after('clave_contrato');
            $table->string('contrato_ruta')->nullable()->after('fecha_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn(['fecha_contrato', 'numero_contrato_oficial', 'contrato_ruta']);
        });
    }
};
