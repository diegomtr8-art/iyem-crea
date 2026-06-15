<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Dashboard
            'dashboard.ver',

            // Interesados / Prospectos
            'interesados.ver',
            'interesados.gestionar',

            // Solicitudes de crédito
            'solicitudes.ver',
            'solicitudes.gestionar',
            'solicitudes.aprobar',
            'solicitudes.rechazar',

            // Acreditados / Créditos activos
            'acreditados.ver',
            'acreditados.gestionar',

            // Análisis crediticio
            'analisis.ver',
            'analisis.gestionar',

            // Desembolsos
            'desembolsos.ver',
            'desembolsos.gestionar',

            // Pagos y cobranza
            'pagos.ver',
            'pagos.registrar',
            'pagos.cancelar',
            'cobranza.ver',
            'cobranza.gestionar',

            // Jurídico
            'juridico.ver',
            'juridico.editar',

            // Presupuesto
            'presupuesto.ver',
            'presupuesto.gestionar',

            // Reportes
            'reportes.ver',

            // Simulador
            'simulador.ver',

            // Auditoría
            'auditoria.ver',

            // Gestión de usuarios y roles (solo admin)
            'usuarios.ver',
            'usuarios.gestionar',
            'roles.ver',
            'roles.gestionar',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // ── Rol: Administrador — acceso total ──────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions($permisos);

        // ── Rol: Analista de Crédito ───────────────────────────────────────
        $analista = Role::firstOrCreate(['name' => 'Analista de Crédito', 'guard_name' => 'web']);
        $analista->syncPermissions([
            'dashboard.ver',
            'interesados.ver', 'interesados.gestionar',
            'solicitudes.ver', 'solicitudes.gestionar',
            'solicitudes.aprobar', 'solicitudes.rechazar',
            'acreditados.ver',
            'analisis.ver', 'analisis.gestionar',
            'desembolsos.ver',
            'reportes.ver', 'simulador.ver',
        ]);

        // ── Rol: Cajero ───────────────────────────────────────────────────
        $cajero = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            'dashboard.ver',
            'acreditados.ver',
            'pagos.ver', 'pagos.registrar',
            'cobranza.ver',
            'reportes.ver',
        ]);

        // ── Rol: Cobranza ─────────────────────────────────────────────────
        $cobranza = Role::firstOrCreate(['name' => 'Cobranza', 'guard_name' => 'web']);
        $cobranza->syncPermissions([
            'dashboard.ver',
            'acreditados.ver',
            'pagos.ver', 'pagos.registrar', 'pagos.cancelar',
            'cobranza.ver', 'cobranza.gestionar',
            'juridico.ver',
            'reportes.ver',
        ]);

        // ── Rol: Jurídico ─────────────────────────────────────────────────
        $juridico = Role::firstOrCreate(['name' => 'Jurídico', 'guard_name' => 'web']);
        $juridico->syncPermissions([
            'dashboard.ver',
            'acreditados.ver',
            'cobranza.ver', 'cobranza.gestionar',
            'juridico.ver', 'juridico.editar',
            'reportes.ver',
        ]);

        // ── Rol: Consulta / Solo lectura ──────────────────────────────────
        $consulta = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);
        $consulta->syncPermissions([
            'dashboard.ver',
            'interesados.ver',
            'solicitudes.ver',
            'acreditados.ver',
            'pagos.ver',
            'cobranza.ver',
            'reportes.ver',
            'simulador.ver',
        ]);

        // ── Rol: Ciudadano — controlado por middleware EsCiudadano + tipo ──
        Role::firstOrCreate(['name' => 'Ciudadano', 'guard_name' => 'web']);

        // Asignar Administrador al primer usuario operativo (si existe)
        $primerAdmin = User::where('tipo', 'operativo')->first();
        if ($primerAdmin && ! $primerAdmin->hasRole('Administrador')) {
            $primerAdmin->assignRole('Administrador');
        }
    }
}
