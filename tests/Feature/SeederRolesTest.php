<?php

use App\Models\AuditoriaLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** Nombres de los permisos de un rol, en orden alfabético. */
function permisosDelRol(string $rol): array
{
    return Role::findByName($rol, 'web')->permissions()->pluck('name')->sort()->values()->all();
}

test('un Cajero existente sigue siendo Cajero tras correr el seeder dos veces', function (string $seeder) {
    $this->seed(RoleAndPermissionSeeder::class);
    // Como en el simulacro de la J.15: el primer usuario es un ciudadano y el primer operativo, un Cajero
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano']);
    $cajero    = User::factory()->create(['tipo' => 'operativo']);
    $cajero->assignRole('Cajero');
    $sinRol = User::factory()->create(['tipo' => 'operativo']);

    $this->seed($seeder);
    $this->seed($seeder);

    expect($cajero->fresh()->getRoleNames()->all())->toBe(['Cajero'])
        ->and($ciudadano->fresh()->getRoleNames()->all())->toBe([])
        ->and($sinRol->fresh()->getRoleNames()->all())->toBe([])
        ->and(User::role('Administrador')->count())->toBe(0);
})->with([
    'RoleAndPermissionSeeder' => RoleAndPermissionSeeder::class,
    'RolesPermissionsSeeder'  => RolesPermissionsSeeder::class,
    'DatabaseSeeder (db:seed)' => DatabaseSeeder::class,
]);

test('en una base vacía crea los roles con sus permisos', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Analista de Crédito', 'Cajero', 'Ciudadano', 'Cobranza', 'Consulta', 'Jurídico'])
        ->and(permisosDelRol('Administrador'))->toHaveCount(29)
        ->toBe(Permission::pluck('name')->sort()->values()->all())
        ->and(permisosDelRol('Cajero'))
        ->toBe(['acreditados.ver', 'cobranza.ver', 'dashboard.ver', 'pagos.registrar', 'pagos.ver', 'reportes.ver'])
        ->and(permisosDelRol('Ciudadano'))->toBe([]);
});

test('respeta lo que se editó en Sistema → Roles', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    // Como lo guarda RoleController@update
    Role::findByName('Cobranza', 'web')->syncPermissions(['dashboard.ver', 'cobranza.ver']);
    Role::findByName('Cajero', 'web')->givePermissionTo('juridico.ver');

    $this->seed(RoleAndPermissionSeeder::class);

    expect(permisosDelRol('Cobranza'))->toBe(['cobranza.ver', 'dashboard.ver'])
        ->and(permisosDelRol('Cajero'))->toContain('juridico.ver');
});

test('un permiso nuevo en el código llega a los roles que lo traen en su lista, sin devolver los que se quitaron', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    // Como si simulador.ver se agregara al seeder después de instalar el sistema...
    Permission::findByName('simulador.ver', 'web')->delete();
    // ...y alguien le hubiera quitado reportes.ver al Analista desde la pantalla
    Role::findByName('Analista de Crédito', 'web')->revokePermissionTo('reportes.ver');

    $this->seed(RoleAndPermissionSeeder::class);

    expect(Permission::findByName('simulador.ver', 'web')->roles()->pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Analista de Crédito', 'Consulta'])
        ->and(permisosDelRol('Analista de Crédito'))->not->toContain('reportes.ver');
});

test('correr los dos seeders de roles ya no le quita permisos al Administrador', function () {
    $this->seed(RolesPermissionsSeeder::class);
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(RolesPermissionsSeeder::class);

    // Antes, cada uno le dejaba solo los suyos y el menú lateral (ver.*) quedaba vacío
    expect(permisosDelRol('Administrador'))
        ->toContain('ver.usuarios', 'ver.acreditados', 'usuarios.ver', 'auditoria.ver')
        ->toHaveCount(8 + 29);
});

test('crea:asignar-administrador le da el rol a un operativo por su correo y lo anota en la bitácora', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $cajero = User::factory()->create(['tipo' => 'operativo', 'email' => 'cajero@prueba.test']);
    $cajero->assignRole('Cajero');

    $this->artisan('crea:asignar-administrador', ['correo' => 'cajero@prueba.test'])
        ->expectsOutputToContain('ahora es Administrador')
        ->assertSuccessful();

    // Conserva el rol que ya tenía
    expect($cajero->fresh()->getRoleNames()->sort()->values()->all())->toBe(['Administrador', 'Cajero'])
        ->and(AuditoriaLog::where('entidad', 'User')->where('entidad_id', $cajero->id)->sole())
        ->accion->toBe('updated')
        ->campo_modificado->toBe('roles')
        ->valor_anterior->toBe('Cajero')
        ->valor_nuevo->toBe('Administrador, Cajero');
});

test('crea:asignar-administrador no cambia nada si ya es Administrador', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $admin = User::factory()->create(['tipo' => 'operativo', 'email' => 'admin@prueba.test']);
    $admin->assignRole('Administrador');

    $this->artisan('crea:asignar-administrador', ['correo' => 'admin@prueba.test'])
        ->expectsOutputToContain('ya es Administrador. No se cambió nada.')
        ->assertSuccessful();

    expect(AuditoriaLog::count())->toBe(0);
});

test('crea:asignar-administrador se niega con un correo que no existe', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    User::factory()->create(['tipo' => 'operativo', 'email' => 'otra@prueba.test']);

    $this->artisan('crea:asignar-administrador', ['correo' => 'nadie@prueba.test'])
        ->expectsOutputToContain('No hay ningún usuario con el correo «nadie@prueba.test»')
        ->assertFailed();

    expect(User::role('Administrador')->count())->toBe(0)
        ->and(AuditoriaLog::count())->toBe(0);
});

test('crea:asignar-administrador se niega con un ciudadano', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    User::factory()->create(['tipo' => 'ciudadano', 'email' => 'ciudadano@prueba.test']);

    $this->artisan('crea:asignar-administrador', ['correo' => 'ciudadano@prueba.test'])
        ->expectsOutputToContain('es un usuario ciudadano')
        ->assertFailed();

    expect(User::role('Administrador')->count())->toBe(0)
        ->and(AuditoriaLog::count())->toBe(0);
});

test('crea:asignar-administrador pide correr antes el seeder si no existe el rol', function () {
    User::factory()->create(['tipo' => 'operativo', 'email' => 'operativo@prueba.test']);

    $this->artisan('crea:asignar-administrador', ['correo' => 'operativo@prueba.test'])
        ->expectsOutputToContain('db:seed --class=RoleAndPermissionSeeder')
        ->assertFailed();

    expect(DB::table('model_has_roles')->count())->toBe(0);
});
