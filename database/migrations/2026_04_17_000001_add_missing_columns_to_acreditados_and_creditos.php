<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de ajuste para bases de datos existentes.
 * Garantiza que acreditados y creditos tengan el esquema correcto
 * independientemente del estado previo.
 *
 * Las migraciones base ya incluyen todos los campos; este archivo
 * solo actúa como parche para instalaciones que venían de versiones anteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── acreditados: agregar campos que faltaban en la migración original ──
        Schema::table('acreditados', function (Blueprint $table) {
            if (!Schema::hasColumn('acreditados', 'clave_personalizada')) {
                $table->string('clave_personalizada')->nullable()->after('correo');
            }
            if (!Schema::hasColumn('acreditados', 'regimen')) {
                $table->string('regimen')->nullable()->after('clave_personalizada');
            }
        });

        // ── creditos: reemplazar tasa_interes_aplicada por las dos tasas correctas ──
        Schema::table('creditos', function (Blueprint $table) {
            if (!Schema::hasColumn('creditos', 'tasa_interes_ordinario')) {
                $table->decimal('tasa_interes_ordinario', 5, 2)->default(0)->after('fecha_entrega');
            }
            if (!Schema::hasColumn('creditos', 'tasa_interes_moratorio')) {
                $table->decimal('tasa_interes_moratorio', 10, 2)->default(0)->after('tasa_interes_ordinario');
            }
            // Eliminar columna legacy si aún existe
            if (Schema::hasColumn('creditos', 'tasa_interes_aplicada')) {
                $table->dropColumn('tasa_interes_aplicada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acreditados', function (Blueprint $table) {
            if (Schema::hasColumn('acreditados', 'clave_personalizada')) {
                $table->dropColumn('clave_personalizada');
            }
            if (Schema::hasColumn('acreditados', 'regimen')) {
                $table->dropColumn('regimen');
            }
        });

        Schema::table('creditos', function (Blueprint $table) {
            if (!Schema::hasColumn('creditos', 'tasa_interes_aplicada')) {
                $table->decimal('tasa_interes_aplicada', 5, 2)->default(0)->after('fecha_entrega');
            }
            if (Schema::hasColumn('creditos', 'tasa_interes_ordinario')) {
                $table->dropColumn('tasa_interes_ordinario');
            }
            if (Schema::hasColumn('creditos', 'tasa_interes_moratorio')) {
                $table->dropColumn('tasa_interes_moratorio');
            }
        });
    }
};
