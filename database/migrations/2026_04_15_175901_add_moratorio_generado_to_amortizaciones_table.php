<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('amortizaciones', function (Blueprint $table) {
        // Este campo guardará la mora calculada al día de hoy
        $table->decimal('interes_moratorio_generado', 12, 2)->default(0)->after('interes_moratorio_pagado');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amortizaciones', function (Blueprint $table) {
            //
        });
    }
};
