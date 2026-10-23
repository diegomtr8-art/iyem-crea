<?php

namespace App\Console\Commands;

use App\Models\AuditoriaLog;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Los seeders ya no le dan el rol Administrador a nadie: se asigna aquí, con el
 * correo de la persona, o desde Sistema → Usuarios por otro Administrador.
 */
class AsignarAdministrador extends Command
{
    protected $signature   = 'crea:asignar-administrador {correo : Correo del usuario operativo}';
    protected $description = 'Le da el rol Administrador a un usuario operativo, buscándolo por su correo';

    public function handle(): int
    {
        $correo  = trim($this->argument('correo'));
        $usuario = User::where('email', $correo)->first();

        if (! $usuario) {
            $this->error("No hay ningún usuario con el correo «{$correo}». No se cambió nada.");

            return self::FAILURE;
        }

        if ($usuario->tipo !== 'operativo') {
            $this->error("«{$correo}» es un usuario ciudadano: solo un operativo puede ser Administrador. No se cambió nada.");

            return self::FAILURE;
        }

        if (! Role::where('name', 'Administrador')->where('guard_name', 'web')->exists()) {
            $this->error('No existe el rol Administrador. Corre antes php artisan db:seed --class=RoleAndPermissionSeeder. No se cambió nada.');

            return self::FAILURE;
        }

        if ($usuario->hasRole('Administrador')) {
            $this->info("{$usuario->name} ({$correo}) ya es Administrador. No se cambió nada.");

            return self::SUCCESS;
        }

        // Se agrega el rol sin quitarle los que ya tenga
        $antes = $usuario->getRoleNames()->sort()->implode(', ');
        $usuario->assignRole('Administrador');
        $despues = $usuario->getRoleNames()->sort()->implode(', ');

        AuditoriaLog::registrar('User', $usuario->id, 'updated', 'roles', $antes ?: null, $despues, 'Rol Administrador asignado con crea:asignar-administrador');

        $this->info("Listo: {$usuario->name} ({$correo}) ahora es Administrador. Sus roles: {$despues}.");

        return self::SUCCESS;
    }
}
