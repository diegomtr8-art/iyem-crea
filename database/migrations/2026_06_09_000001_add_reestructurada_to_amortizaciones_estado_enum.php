<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no tiene ENUM ni MODIFY COLUMN — solo aplica en MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE amortizaciones MODIFY COLUMN estado ENUM('Pendiente','Parcial','Pagado','Condonado','Reestructurada') DEFAULT 'Pendiente'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('amortizaciones')->where('estado', 'Reestructurada')->update(['estado' => 'Condonado']);
            DB::statement("ALTER TABLE amortizaciones MODIFY COLUMN estado ENUM('Pendiente','Parcial','Pagado','Condonado') DEFAULT 'Pendiente'");
        }
    }
};
