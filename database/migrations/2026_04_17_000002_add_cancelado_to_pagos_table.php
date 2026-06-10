<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos', 'cancelado')) {
                $table->boolean('cancelado')->default(false)->after('observaciones');
            }
            if (!Schema::hasColumn('pagos', 'cancelado_por')) {
                $table->foreignId('cancelado_por')->nullable()->constrained('users')->after('cancelado');
            }
            if (!Schema::hasColumn('pagos', 'cancelado_at')) {
                $table->timestamp('cancelado_at')->nullable()->after('cancelado_por');
            }
            if (!Schema::hasColumn('pagos', 'motivo_cancelacion')) {
                $table->string('motivo_cancelacion')->nullable()->after('cancelado_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['cancelado', 'cancelado_por', 'cancelado_at', 'motivo_cancelacion']);
        });
    }
};
