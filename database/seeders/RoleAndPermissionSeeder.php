<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CreaRolesYPermisosQueFaltan;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    use CreaRolesYPermisosQueFaltan;

    public function run(): void
    {
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

        $this->crearRolesYPermisosQueFaltan([
            // ── Rol: Administrador — acceso total ──────────────────────────────
            'Administrador' => $permisos,

            // ── Rol: Analista de Crédito ───────────────────────────────────────
            'Analista de Crédito' => [
                'dashboard.ver',
                'interesados.ver', 'interesados.gestionar',
                'solicitudes.ver', 'solicitudes.gestionar',
                'solicitudes.aprobar', 'solicitudes.rechazar',
                'acreditados.ver',
                'analisis.ver', 'analisis.gestionar',
                'desembolsos.ver',
                'reportes.ver', 'simulador.ver',
            ],

            // ── Rol: Cajero ───────────────────────────────────────────────────
            'Cajero' => [
                'dashboard.ver',
                'acreditados.ver',
                'pagos.ver', 'pagos.registrar',
                'cobranza.ver',
                'reportes.ver',
            ],

            // ── Rol: Cobranza ─────────────────────────────────────────────────
            'Cobranza' => [
                'dashboard.ver',
                'acreditados.ver',
                'pagos.ver', 'pagos.registrar', 'pagos.cancelar',
                'cobranza.ver', 'cobranza.gestionar',
                'juridico.ver',
                'reportes.ver',
            ],

            // ── Rol: Jurídico ─────────────────────────────────────────────────
            'Jurídico' => [
                'dashboard.ver',
                'acreditados.ver',
                'cobranza.ver', 'cobranza.gestionar',
                'juridico.ver', 'juridico.editar',
                'reportes.ver',
            ],

            // ── Rol: Consulta / Solo lectura ──────────────────────────────────
            'Consulta' => [
                'dashboard.ver',
                'interesados.ver',
                'solicitudes.ver',
                'acreditados.ver',
                'pagos.ver',
                'cobranza.ver',
                'reportes.ver',
                'simulador.ver',
            ],

            // ── Rol: Ciudadano — controlado por middleware EsCiudadano + tipo ──
            'Ciudadano' => [],
        ]);

        // Ya no se le da el rol Administrador "al primer operativo": en una base con
        // usuarios ese puede ser un Cajero. Se asigna con crea:asignar-administrador.
    }
}
