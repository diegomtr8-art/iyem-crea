<?php

use App\Models\BitacoraTareaProgramada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/** Programa una tarea que truena y corre el scheduler, igual que lo hace el cron del servidor. */
function simularFalloDeTareaProgramada(): void
{
    Schedule::call(fn () => throw new RuntimeException('Fallo de prueba'))
        ->name('tarea-de-prueba')
        ->everyMinute();

    test()->artisan('schedule:run');
}

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-02 18:00', 'UTC')); // 12:00 en Mérida: no coincide con las tareas diarias

    Role::create(['name' => 'Administrador']);
    $this->admin = User::factory()->create(['tipo' => 'operativo']);
    $this->admin->assignRole('Administrador');
    $this->operativo = User::factory()->create(['tipo' => 'operativo']);
});

test('cuando una tarea programada falla, cada administrador recibe una alerta', function () {
    simularFalloDeTareaProgramada();

    expect(BitacoraTareaProgramada::where('tarea', 'tarea-de-prueba')->value('estado'))->toBe('error')
        ->and($this->admin->unreadNotifications)->toHaveCount(1)
        ->and($this->admin->unreadNotifications->first()->data)->toMatchArray([
            'tarea'   => 'tarea-de-prueba',
            'mensaje' => 'Fallo de prueba',
        ])
        ->and($this->operativo->notifications)->toHaveCount(0);
});

test('si un comando termina con error se avisa una sola vez', function () {
    // Así llegan los eventos cuando falla un comando: primero Finished (código 1) y luego Failed
    $tarea = new Event(app(CacheEventMutex::class), Artisan::formatCommandString('crea:comando-de-prueba'));
    $tarea->exitCode = 1;

    event(new ScheduledTaskStarting($tarea));
    event(new ScheduledTaskFinished($tarea, 0.5));
    event(new ScheduledTaskFailed($tarea, new RuntimeException('Scheduled command failed with exit code [1].')));

    expect($this->admin->unreadNotifications)->toHaveCount(1)
        ->and($this->admin->unreadNotifications->first()->data['tarea'])->toBe('crea:comando-de-prueba');
});

test('una tarea que termina bien no genera alerta', function () {
    Schedule::call(fn () => null)->name('tarea-sin-errores')->everyMinute();

    $this->artisan('schedule:run');

    expect(BitacoraTareaProgramada::where('tarea', 'tarea-sin-errores')->value('estado'))->toBe('exito')
        ->and($this->admin->notifications)->toHaveCount(0);
});

test('el administrador ve el contador en cualquier pantalla del panel sin recargar nada a mano', function () {
    simularFalloDeTareaProgramada();

    $this->actingAs($this->admin)->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fallos_tareas.total', 1)
            ->where('fallos_tareas.recientes.0.tarea', 'tarea-de-prueba'));

    $this->actingAs($this->operativo)->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('fallos_tareas', null));
});

test('al abrir las alertas se marcan como leídas', function () {
    simularFalloDeTareaProgramada();
    $ids = $this->admin->unreadNotifications->pluck('id')->all();

    $this->actingAs($this->admin)
        ->post(route('notificaciones-tareas.leidas'), ['ids' => $ids])
        ->assertRedirect();

    expect($this->admin->fresh()->unreadNotifications)->toHaveCount(0);

    $this->actingAs($this->admin)->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('fallos_tareas.total', 0));
});

test('quien no es Administrador no puede marcar alertas', function () {
    $this->actingAs($this->operativo)
        ->post(route('notificaciones-tareas.leidas'), ['ids' => ['x']])
        ->assertForbidden();
});