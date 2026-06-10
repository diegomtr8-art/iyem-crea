<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios_ciudadano', function (Blueprint $table) {
            $table->id();
            // null = anuncio global para todos los ciudadanos
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('tipo', ['info', 'alerta', 'pago', 'exito', 'error'])->default('info');
            $table->boolean('leido')->default(false);
            $table->string('url_accion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios_ciudadano');
    }
};
