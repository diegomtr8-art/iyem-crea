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
       Schema::create('modalidad_creas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre'); // Artesanal, Emprendedores, Sustentable
                $table->decimal('tasa_interes', 5, 2); // 0.00, 7.00, 5.00
                $table->decimal('tasa_moratoria', 5, 2)->default(10.00); 
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modalidad_creas');
    }
};
