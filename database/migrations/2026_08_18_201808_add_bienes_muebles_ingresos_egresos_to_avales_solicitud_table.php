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
        Schema::table('avales_solicitud', function (Blueprint $table) {
            $table->json('bienes_muebles')->nullable()->after('bienes_inmuebles');
            $table->json('ingresos')->nullable()->after('otras_deudas');
            $table->json('egresos')->nullable()->after('ingresos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avales_solicitud', function (Blueprint $table) {
            $table->dropColumn(['bienes_muebles', 'ingresos', 'egresos']);
        });
    }
};
