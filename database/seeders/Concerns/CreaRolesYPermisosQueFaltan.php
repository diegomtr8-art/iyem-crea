<?php

namespace Database\Seeders\Concerns;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los roles y permisos que falten sin tocar lo que ya existe.
 *
 * Se puede correr las veces que sea, también sobre una base restaurada: no le quita
 * permisos a ningún rol (lo editado en Sistema → Roles se respeta) y no le cambia el
 * rol a ningún usuario. El Administrador se asigna con `php artisan crea:asignar-administrador`.
 */
trait CreaRolesYPermisosQueFaltan
{
    /**
     * @param  array<string, list<string>>  $roles  Cada rol con los permisos con los que nace
     */
    protected function crearRolesYPermisosQueFaltan(array $roles): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisosNuevos = [];
        foreach (array_unique(array_merge(...array_values($roles))) as $nombre) {
            if (Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web'])->wasRecentlyCreated) {
                $permisosNuevos[] = $nombre;
            }
        }

        foreach ($roles as $nombre => $permisos) {
            $rol = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);

            // Un rol nuevo nace con su lista. A uno que ya existía solo se le agregan los
            // permisos que se acaban de crear, así que nunca recupera uno que le quitaron.
            $porDar = $rol->wasRecentlyCreated ? $permisos : array_values(array_intersect($permisos, $permisosNuevos));
            if ($porDar) {
                $rol->givePermissionTo($porDar);
            }
        }
    }
}
