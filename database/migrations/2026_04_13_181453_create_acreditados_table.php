<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('rfc', 13)->unique()->nullable();
            $table->string('curp', 18)->unique()->nullable();
            $table->string('municipio');
            $table->string('correo')->nullable();
            $table->enum('sexo', ['H', 'M', 'O'])->nullable();
            $table->text('direccion_fiscal')->nullable();
            $table->string('clave_personalizada')->nullable();
            $table->string('regimen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditados');
    }
};
