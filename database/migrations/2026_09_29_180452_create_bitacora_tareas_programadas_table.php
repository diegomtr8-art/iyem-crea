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
        Schema::create('bitacora_tareas_programadas', function (Blueprint $table) {
            $table->id();
            $table->string('tarea');                              // p. ej. crea:update-moratorio
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->decimal('duracion_segundos', 10, 2)->nullable();
            $table->string('estado', 20)->default('en_curso');    // en_curso | exito | error
            $table->smallInteger('codigo_salida')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->index(['tarea', 'inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_tareas_programadas');
    }
};
