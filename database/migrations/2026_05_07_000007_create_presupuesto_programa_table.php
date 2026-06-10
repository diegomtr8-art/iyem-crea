<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_programa', function (Blueprint $table) {
            $table->id();
            $table->integer('ejercicio_fiscal');
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad_creas')->nullOnDelete();
            $table->decimal('monto_autorizado', 15, 2);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['ejercicio_fiscal', 'modalidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_programa');
    }
};
