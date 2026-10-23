<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteRecuperacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba que un usuario operativo autenticado puede acceder al reporte de recuperación.
     */
    public function test_usuario_operativo_puede_acceder_al_reporte_de_recuperacion(): void
    {
        $usuario = User::factory()->create([
            'tipo' => 'operativo',
        ]);

        $response = $this->actingAs($usuario)
            ->get(route('reportes.recuperacion'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reportes/Recuperacion')
            ->has('reporte')
            ->has('modalidades')
            ->has('municipios')
            ->has('regla_fecha')
        );
    }

    /**
     * Prueba que el usuario operativo puede solicitar la exportación del reporte en CSV.
     */
    public function test_usuario_operativo_puede_exportar_reporte_recuperacion(): void
    {
        $usuario = User::factory()->create([
            'tipo' => 'operativo',
        ]);

        $response = $this->actingAs($usuario)
            ->get(route('exportar.recuperacion'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}