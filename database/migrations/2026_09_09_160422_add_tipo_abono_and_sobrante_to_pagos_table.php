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
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('tipo_abono')->nullable()->after('aplicado_capital');
            $table->decimal('sobrante_aplicado', 15, 2)->nullable()->after('tipo_abono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['tipo_abono', 'sobrante_aplicado']);
        });
    }
};
