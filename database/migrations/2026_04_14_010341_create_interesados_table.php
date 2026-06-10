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
        Schema::create('interesados', function (Blueprint $table) {
    $table->id(); // Este será nuestro número de registro
    $table->enum('medio_ingreso', ['Oficina', 'Teléfono', 'Correo', 'Redes Sociales', 'Otro']);
    $table->string('nombre_ciudadano');
    $table->string('empresa')->nullable();
    $table->string('correo_electronico')->nullable();
    $table->string('telefono', 15)->nullable();
    $table->enum('sexo', ['Hombre', 'Mujer', 'Otro']);
    $table->string('municipio'); // Podríamos luego normalizarlo a una tabla de municipios
    $table->date('fecha_nacimiento')->nullable();
    $table->boolean('mayahablante')->default(false);
    $table->boolean('discapacidad')->default(false);
    $table->string('giro_comercial')->nullable();
    $table->boolean('alta_sat')->default(false);
    $table->text('destino_credito')->nullable();
    $table->string('modalidad'); // Artesanal, Sustentable, Emprendedores
    
    // Seguimiento
    $table->string('atendio'); // Nombre del empleado que recibió
    $table->string('seguimiento'); // Nombre del empleado responsable
    
    $table->enum('estatus', ['Pendiente', 'Aprobado', 'Rechazado', 'Convertido'])->default('Pendiente');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interesados');
    }
};
