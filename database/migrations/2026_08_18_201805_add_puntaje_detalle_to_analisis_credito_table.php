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
        Schema::table('analisis_credito', function (Blueprint $table) {
            $table->json('puntaje_detalle')->nullable()->after('puntaje_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analisis_credito', function (Blueprint $table) {
            $table->dropColumn('puntaje_detalle');
        });
    }
};
