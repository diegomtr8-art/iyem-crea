<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE amortizaciones MODIFY COLUMN estado ENUM('Pendiente','Parcial','Pagado','Condonado','Reestructurada','Gracia') DEFAULT 'Pendiente'");

        if (!Schema::hasColumn('amortizaciones', 'observaciones')) {
            Schema::table('amortizaciones', function (Blueprint $table) {
                $table->string('observaciones')->nullable()->after('fecha_ultimo_pago');
            });
        }
    }

    public function down(): void
    {
        DB::table('amortizaciones')->where('estado', 'Gracia')->update(['estado' => 'Pagado']);
        DB::statement("ALTER TABLE amortizaciones MODIFY COLUMN estado ENUM('Pendiente','Parcial','Pagado','Condonado','Reestructurada') DEFAULT 'Pendiente'");

        if (Schema::hasColumn('amortizaciones', 'observaciones')) {
            Schema::table('amortizaciones', function (Blueprint $table) {
                $table->dropColumn('observaciones');
            });
        }
    }
};
