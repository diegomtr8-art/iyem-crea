<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_tareas_programadas', function (Blueprint $table) {
            // Lo que reportó la tarea al terminar, p. ej. "OK: crea-respaldo-…" o cuántas filas borró la limpieza
            $table->string('resultado', 500)->nullable()->after('mensaje_error');
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_tareas_programadas', function (Blueprint $table) {
            $table->dropColumn('resultado');
        });
    }
};
