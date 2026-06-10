<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalidad_creas', function (Blueprint $table) {
            // descripcion y tasa_moratoria ya existen en la tabla
            $table->decimal('monto_minimo', 12, 2)->nullable()->after('tasa_moratoria');
            $table->decimal('monto_maximo', 12, 2)->nullable()->after('monto_minimo');
            $table->integer('plazo_min_meses')->nullable()->after('monto_maximo');
            $table->integer('plazo_max_meses')->nullable()->after('plazo_min_meses');
            $table->boolean('requiere_alta_sat')->default(false)->after('plazo_max_meses');
            $table->boolean('requiere_antiguedad')->default(false)->after('requiere_alta_sat');
            $table->json('documentos_requeridos')->nullable()->after('requiere_antiguedad');
            $table->text('municipios_elegibles')->nullable()->after('documentos_requeridos');
            $table->boolean('activo')->default(true)->after('municipios_elegibles');
        });
    }

    public function down(): void
    {
        Schema::table('modalidad_creas', function (Blueprint $table) {
            $table->dropColumn([
                'descripcion', 'tasa_moratoria', 'monto_minimo', 'monto_maximo',
                'plazo_min_meses', 'plazo_max_meses', 'requiere_alta_sat',
                'requiere_antiguedad', 'documentos_requeridos', 'municipios_elegibles', 'activo',
            ]);
        });
    }
};
