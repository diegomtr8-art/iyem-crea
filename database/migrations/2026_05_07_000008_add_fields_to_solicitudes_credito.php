<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete()->after('observaciones');
            $table->timestamp('fecha_asignacion')->nullable()->after('asignado_a');
            $table->string('motivo_rechazo')->nullable()->after('fecha_asignacion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->dropForeign(['asignado_a']);
            $table->dropColumn(['asignado_a', 'fecha_asignacion', 'motivo_rechazo']);
        });
    }
};
