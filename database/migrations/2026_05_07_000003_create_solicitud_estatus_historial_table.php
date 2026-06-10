<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_estatus_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_credito')->cascadeOnDelete();
            $table->string('estatus_anterior')->nullable();
            $table->string('estatus_nuevo');
            $table->text('observaciones')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_estatus_historial');
    }
};
