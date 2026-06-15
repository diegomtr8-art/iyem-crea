<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->enum('tipo_persona', ['fisica', 'moral'])->default('fisica')->nullable()->after('modalidad_id');
            $table->smallInteger('plazo_meses')->nullable()->after('monto_solicitado');
            $table->enum('tipo_garantia', ['aval', 'prendaria', 'hipotecaria'])->nullable()->after('plazo_meses');
            $table->json('datos_wizard')->nullable()->after('tipo_garantia');
            $table->string('formatos_zip_ruta')->nullable()->after('datos_wizard');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->dropColumn(['tipo_persona', 'plazo_meses', 'tipo_garantia', 'datos_wizard', 'formatos_zip_ruta']);
        });
    }
};
