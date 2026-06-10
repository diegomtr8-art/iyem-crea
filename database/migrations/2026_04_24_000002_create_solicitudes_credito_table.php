<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos personales
            $table->string('nombre_completo')->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->boolean('mayahablante')->default(false);
            $table->boolean('discapacidad')->default(false);
            $table->string('municipio')->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('correo')->nullable();

            // Datos del proyecto / negocio
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad_creas')->nullOnDelete();
            $table->string('giro_comercial')->nullable();
            $table->text('destino_credito')->nullable();
            $table->text('descripcion_negocio')->nullable();
            $table->decimal('monto_solicitado', 12, 2)->nullable();
            $table->boolean('alta_sat')->default(false);

            // Flujo de aprobación
            $table->enum('estatus', [
                'Borrador',
                'Enviada',
                'En_Revision',
                'Documentacion_Incompleta',
                'Aprobada',
                'Rechazada',
            ])->default('Borrador');
            $table->text('observaciones')->nullable();

            // Vínculos cuando es aprobada
            $table->foreignId('acreditado_id')->nullable()->constrained('acreditados')->nullOnDelete();
            $table->foreignId('credito_id')->nullable()->constrained('creditos')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_credito');
    }
};
