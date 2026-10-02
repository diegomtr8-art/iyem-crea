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
        Schema::table('amortizaciones', function (Blueprint $table) {
            $table->timestamp('recordatorio_enviado_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amortizaciones', function (Blueprint $table) {
            $table->dropColumn('recordatorio_enviado_at');
        });
    }
};
