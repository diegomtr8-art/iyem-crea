<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avales_solicitud', function (Blueprint $table) {
            $table->string('parentesco')->nullable()->after('nombre_completo');
        });
    }

    public function down(): void
    {
        Schema::table('avales_solicitud', function (Blueprint $table) {
            $table->dropColumn('parentesco');
        });
    }
};
