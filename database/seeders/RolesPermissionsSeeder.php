<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            'ver.dashboard',
            'ver.interesados',
            'ver.acreditados',
            'ver.pagos',
            'ver.reportes',
            'ver.simulador',
            'ver.usuarios',
            'ver.roles',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // Rol Administrador — acceso total
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions($permisos);

        // Rol Operativo — puede ver todo menos usuarios y roles
        $operativo = Role::firstOrCreate(['name' => 'Operativo', 'guard_name' => 'web']);
        $operativo->syncPermissions([
            'ver.dashboard',
            'ver.interesados',
            'ver.acreditados',
            'ver.pagos',
            'ver.reportes',
            'ver.simulador',
        ]);

        // Asignar rol Administrador al primer usuario (si existe)
        $primerAdmin = User::first();
        if ($primerAdmin && !$primerAdmin->hasRole('Administrador')) {
            $primerAdmin->assignRole('Administrador');
        }
    }
}
