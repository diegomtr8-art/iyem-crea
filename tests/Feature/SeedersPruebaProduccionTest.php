<?php

use App\Models\Acreditado;
use App\Models\Credito;
use Database\Seeders\Add30CreditosSeeder;
use Database\Seeders\TestCreditosSeeder;
use Database\Seeders\TestDataSeeder;

/**
 * Los seeders de datos de prueba no deben poder correr en producción.
 */
test('los seeders de prueba se niegan a correr en producción', function (string $seeder) {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app($seeder)->run())
        ->toThrow(RuntimeException::class, 'no se puede correr en producción');

    expect(Acreditado::count())->toBe(0)
        ->and(Credito::count())->toBe(0);
})->with([
    TestDataSeeder::class,
    Add30CreditosSeeder::class,
    TestCreditosSeeder::class,
]);
