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
        Schema::table('mensajes_ciudadano', function (Blueprint $table) {
            // Nulo = pendiente. Se conserva quién lo marcó aunque el usuario se borre después.
            $table->timestamp('atendido_at')->nullable()->index();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensajes_ciudadano', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atendido_por');
            $table->dropIndex(['atendido_at']);
            $table->dropColumn('atendido_at');
        });
    }
};
