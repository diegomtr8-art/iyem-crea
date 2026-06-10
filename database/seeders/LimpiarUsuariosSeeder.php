<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LimpiarUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener IDs de los usuarios a conservar
        $emailsConservar = ['diegomtr8@gmail.com', 'tester@crea.com'];

        // Obtener IDs de los usuarios a conservar (o crearlos si no existen)
        $userDiego = User::firstOrCreate(
            ['email' => 'diegomtr8@gmail.com'],
            [
                'name'              => 'Diego Martinez',
                'password'          => Hash::make('Admin1234!'),
                'tipo'              => 'operativo',
                'email_verified_at' => now(),
            ]
        );

        $userTester = User::firstOrCreate(
            ['email' => 'tester@crea.com'],
            [
                'name'              => 'Tester CREA',
                'password'          => Hash::make('Tester1234!'),
                'tipo'              => 'operativo',
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol Administrador a Diego
        $adminRole = Role::where('name', 'Administrador')->first();
        $operativoRole = Role::where('name', 'Operativo')->first();

        if ($adminRole) {
            $userDiego->syncRoles([$adminRole]);
        }

        if ($operativoRole) {
            $userTester->syncRoles([$operativoRole]);
        }

        // Eliminar todos los demás usuarios operativos (no ciudadanos para no perder solicitudes)
        User::whereNotIn('email', $emailsConservar)
            ->where('tipo', 'operativo')
            ->delete();

        $this->command->info('Usuarios limpiados. Solo quedan:');
        $this->command->info("- {$userDiego->email} (Administrador)");
        $this->command->info("- {$userTester->email} (Operativo/Tester)");
    }
}
