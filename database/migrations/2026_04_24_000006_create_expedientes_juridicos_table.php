<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes_juridicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->unique()->constrained('creditos')->cascadeOnDelete();
            $table->foreignId('acreditado_id')->constrained('acreditados');
            $table->foreignId('usuario_id')->constrained('users');
            $table->date('fecha_envio_juridico');
            $table->enum('estatus', ['Pendiente', 'En_Demanda', 'En_Platicas', 'Reestructurada', 'Recuperada'])->default('Pendiente');
            $table->string('tribunal', 255)->nullable();
            $table->string('juzgado', 255)->nullable();
            $table->string('numero_expediente_judicial', 100)->nullable();
            $table->decimal('monto_reclamado', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes_juridicos');
    }
};
