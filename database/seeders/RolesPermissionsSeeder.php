<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CreaRolesYPermisosQueFaltan;
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    use CreaRolesYPermisosQueFaltan;

    public function run(): void
    {
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

        $this->crearRolesYPermisosQueFaltan([
            // Rol Administrador — acceso total
            'Administrador' => $permisos,

            // Rol Operativo — puede ver todo menos usuarios y roles
            'Operativo' => [
                'ver.dashboard',
                'ver.interesados',
                'ver.acreditados',
                'ver.pagos',
                'ver.reportes',
                'ver.simulador',
            ],
        ]);

        // Ya no se le da el rol Administrador "al primer usuario": podía ser un ciudadano
        // o un Cajero. Se asigna con crea:asignar-administrador.
    }
}
