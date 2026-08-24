<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_credito', function (Blueprint $table) {
            $table->unsignedInteger('puntaje_normativa')->nullable()->after('score_cualitativo');
            $table->unsignedInteger('puntaje_tecnica')->nullable()->after('puntaje_normativa');
            $table->unsignedInteger('puntaje_financiera')->nullable()->after('puntaje_tecnica');
            $table->unsignedInteger('puntaje_impacto_ambiental')->nullable()->after('puntaje_financiera');
            $table->unsignedInteger('puntaje_total')->nullable()->after('puntaje_impacto_ambiental');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_credito', function (Blueprint $table) {
            $table->dropColumn([
                'puntaje_normativa',
                'puntaje_tecnica',
                'puntaje_financiera',
                'puntaje_impacto_ambiental',
                'puntaje_total',
            ]);
        });
    }
};
