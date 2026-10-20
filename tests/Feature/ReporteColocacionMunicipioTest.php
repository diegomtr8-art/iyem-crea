<?php

namespace Tests\Feature;

use App\Models\User;
// REMUEVE ESTA LÍNEA: use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteColocacionMunicipioTest extends TestCase
{
    // NO uses RefreshDatabase aquí para que no toque tu BD principal
    
    /** @test */
    public function usuario_autenticado_puede_ver_el_reporte_de_colocacion_por_municipio()
    {
        $user = User::where('tipo', 'operativo')->first() ?? User::factory()->create(['tipo' => 'operativo']);

        $response = $this->actingAs($user)
            ->get(route('reportes.colocacion-municipio'));

        $response->assertStatus(200);
    }

    /** @test */
    public function usuario_autenticado_puede_exportar_el_reporte_de_colocacion_por_municipio()
    {
        $user = User::where('tipo', 'operativo')->first() ?? User::factory()->create(['tipo' => 'operativo']);

        $response = $this->actingAs($user)
            ->get(route('reportes.colocacion-municipio.exportar'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}