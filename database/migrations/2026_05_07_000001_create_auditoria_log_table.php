<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_log', function (Blueprint $table) {
            $table->id();
            $table->string('entidad', 100);
            $table->unsignedBigInteger('entidad_id');
            $table->enum('accion', ['created', 'updated', 'deleted']);
            $table->string('campo_modificado')->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['entidad', 'entidad_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_log');
    }
};
