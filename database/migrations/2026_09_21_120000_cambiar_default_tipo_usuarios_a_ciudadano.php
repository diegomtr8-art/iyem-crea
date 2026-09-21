<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el valor por defecto de users.tipo de 'operativo' a 'ciudadano'.
 *
 * Motivo (21-09-2026): la ruta pública POST /register creaba usuarios sin
 * asignar 'tipo', por lo que tomaban el default 'operativo' y salían con
 * acceso completo al panel. La ruta ya se eliminó, pero el default seguía
 * siendo una trampa: cualquier alta futura que olvide el campo heredaba
 * privilegios operativos.
 *
 * Con este cambio, un olvido produce una cuenta de ciudadano —inofensiva—
 * en lugar de una operativa. Los tres lugares que sí crean usuarios
 * (UserController, CiudadanoRegisterController y GoogleAuthController)
 * asignan 'tipo' de forma explícita.
 *
 * NO modifica ningún usuario existente: un DEFAULT solo aplica a
 * inserciones nuevas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `users` MODIFY COLUMN `tipo` ENUM('operativo','ciudadano') NOT NULL DEFAULT 'ciudadano'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `users` MODIFY COLUMN `tipo` ENUM('operativo','ciudadano') NOT NULL DEFAULT 'operativo'"
        );
    }
};
