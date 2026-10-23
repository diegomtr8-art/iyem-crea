<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_ciudadano', function (Blueprint $table) {
            $table->id();
            // nullOnDelete: el mensaje se conserva aunque se borre el usuario.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('asunto', 255);
            $table->text('mensaje');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_ciudadano');
    }
};
