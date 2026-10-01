<?php

use App\Models\AccesoCiudadano;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-01 02:00:00', 'UTC'));
});

test('cada login exitoso del ciudadano agrega un registro con su fecha y hora', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    foreach ([0, 1, 2] as $minutos) {
        $this->travelTo(Carbon::parse('2026-10-01 02:00:00', 'UTC')->addMinutes($minutos));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('portal.dashboard', absolute: false));

        $this->post('/logout');
    }

    $accesos = AccesoCiudadano::where('user_id', $user->id)->orderBy('id')->get();

    expect($accesos)->toHaveCount(3);
    expect($accesos->pluck('created_at')->map->toDateTimeString()->all())->toBe([
        '2026-10-01 02:00:00',
        '2026-10-01 02:01:00',
        '2026-10-01 02:02:00',
    ]);
    expect($accesos->first()->ip_address)->not->toBeNull();
});

test('el login de un operativo no genera registro', function () {
    $user = User::factory()->create(['tipo' => 'operativo']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticated();
    expect(AccesoCiudadano::count())->toBe(0);
});

test('un login fallido no genera registro', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);

    $this->assertGuest();
    expect(AccesoCiudadano::count())->toBe(0);
});

test('al eliminar al usuario se conserva el registro de accesos', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->post('/logout');

    $user->delete();

    expect(AccesoCiudadano::count())->toBe(1);
    expect(AccesoCiudadano::first()->user_id)->toBeNull();
});

function accesosDePantalla($response): array
{
    return $response->original->getData()['page']['props']['accesos'];
}

test('la pantalla muestra solo los accesos del ciudadano en sesión, con hora de Mérida', function () {
    $user  = User::factory()->create(['tipo' => 'ciudadano']);
    $otro  = User::factory()->create(['tipo' => 'ciudadano']);

    AccesoCiudadano::create(['user_id' => $user->id, 'ip_address' => '10.0.0.1']);
    AccesoCiudadano::create(['user_id' => $otro->id, 'ip_address' => '10.0.0.2']);

    $accesos = accesosDePantalla($this->actingAs($user)->get(route('portal.accesos'))->assertOk());

    expect($accesos)->toHaveCount(1);
    expect($accesos[0]['ip_address'])->toBe('10.0.0.1');
    // 02:00 UTC del 01/10 = 20:00 del 30/09 en Mérida
    expect($accesos[0]['fecha'])->toBe('30/09/2026');
    expect($accesos[0]['hora'])->toBe('20:00:00');
});

test('la pantalla limita a los últimos 20 accesos, del más reciente al más antiguo', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    foreach (range(1, 25) as $i) {
        $this->travelTo(Carbon::parse('2026-10-01 02:00:00', 'UTC')->addMinutes($i));
        AccesoCiudadano::create(['user_id' => $user->id]);
    }

    $accesos = accesosDePantalla($this->actingAs($user)->get(route('portal.accesos'))->assertOk());

    expect($accesos)->toHaveCount(20);
    expect($accesos[0]['id'])->toBe(25);
});

test('la pantalla de accesos no es accesible para un operativo ni para visitantes', function () {
    $this->get(route('portal.accesos'))->assertRedirect(route('login'));

    $operativo = User::factory()->create(['tipo' => 'operativo']);
    $this->actingAs($operativo)->get(route('portal.accesos'))->assertRedirect(route('dashboard'));
});
