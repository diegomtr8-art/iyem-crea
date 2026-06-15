<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avales_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')
                ->constrained('solicitudes_credito')
                ->cascadeOnDelete();

            // Datos personales del aval
            $table->string('nombre_completo');
            $table->string('correo')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('telefono_celular', 20)->nullable();
            $table->string('telefono_fijo', 20)->nullable();
            $table->tinyInteger('edad')->unsigned()->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('municipio_nacimiento')->nullable();
            $table->string('municipio_residencia')->nullable();
            $table->text('domicilio')->nullable();
            $table->string('colonia')->nullable();
            $table->string('cp', 10)->nullable();
            $table->boolean('domicilio_propio')->default(true);
            $table->decimal('renta_mensual', 10, 2)->nullable();
            $table->string('lugar_laboral')->nullable();
            $table->string('antiguedad_laboral')->nullable();
            $table->string('ocupacion')->nullable();
            $table->date('fecha_inicio_actividades')->nullable();
            $table->tinyInteger('dependientes_economicos')->unsigned()->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('regimen_matrimonial')->nullable();
            $table->string('nombre_conyuge')->nullable();

            // Bienes, deudas y referencias (JSON)
            $table->json('bienes_inmuebles')->nullable();
            $table->json('hipotecas_creditos')->nullable();
            $table->json('otras_deudas')->nullable();
            $table->json('referencias_personales')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avales_solicitud');
    }
};
