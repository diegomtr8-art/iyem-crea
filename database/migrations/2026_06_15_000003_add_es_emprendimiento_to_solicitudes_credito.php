<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->boolean('es_emprendimiento')->default(false)->after('alta_sat');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_credito', function (Blueprint $table) {
            $table->dropColumn('es_emprendimiento');
        });
    }
};
