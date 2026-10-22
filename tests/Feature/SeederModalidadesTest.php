<?php

use App\Models\ModalidadCrea;
use Database\Seeders\ActualizarModalidadesSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ModalidadCreaSeeder;
use Illuminate\Support\Facades\Artisan;

test('php artisan db:seed dos veces no duplica las modalidades', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(ModalidadCrea::count())->toBe(3);
});

test('completa con los datos de 2025 las modalidades que dejó ModalidadCreaSeeder', function () {
    $this->seed(ModalidadCreaSeeder::class);

    Artisan::call('db:seed', ['--class' => ActualizarModalidadesSeeder::class]);

    $sustentable = ModalidadCrea::where('nombre', 'Sustentable')->sole();
    expect(Artisan::output())->toContain("Modalidad 'Sustentable' completada")
        ->and(ModalidadCrea::count())->toBe(3)
        ->and($sustentable->monto_maximo)->toBe('500000.00')
        ->and($sustentable->plazo_max_meses)->toEqual(24)
        ->and($sustentable->requiere_alta_sat)->toBeTrue()
        ->and($sustentable->documentos_requeridos)->toHaveKey('plan_negocio');
});

test('crea con los datos de 2025 la modalidad que falte', function () {
    Artisan::call('db:seed', ['--class' => ActualizarModalidadesSeeder::class]);

    $emprendedores = ModalidadCrea::where('nombre', 'Emprendedores')->sole();
    expect(Artisan::output())->toContain("Modalidad 'Emprendedores' creada")
        ->and(ModalidadCrea::count())->toBe(3)
        ->and($emprendedores->tasa_interes)->toBe('7.00')
        ->and($emprendedores->tasa_moratoria)->toBe('17.50')
        // Un arreglo, no un texto JSON guardado dos veces
        ->and($emprendedores->documentos_requeridos)->toBeArray()->toHaveKey('constancia_situacion');
});

test('no pisa una modalidad que ya se configuró y avisa en qué difiere de 2025', function () {
    $this->seed(ActualizarModalidadesSeeder::class);
    // Cambios hechos a mano después de instalar
    ModalidadCrea::where('nombre', 'Emprendedores')->update(['tasa_interes' => 6.5, 'monto_maximo' => 200000, 'requiere_alta_sat' => false]);
    $antes = ModalidadCrea::where('nombre', 'Emprendedores')->sole()->getAttributes();

    Artisan::call('db:seed', ['--class' => ActualizarModalidadesSeeder::class]);
    $salida = Artisan::output();

    expect(ModalidadCrea::where('nombre', 'Emprendedores')->sole()->getAttributes())->toBe($antes)
        ->and($salida)
        ->toContain("Modalidad 'Emprendedores' ya estaba configurada: no se cambió.")
        ->toContain('tasa_interes (hoy 6.50, 2025: 7.00)')
        ->toContain('monto_maximo (hoy 200000.00, 2025: 150000.00)')
        ->toContain('requiere_alta_sat (hoy no, 2025: sí)')
        // Las otras dos siguen igual que en 2025: no hay nada que avisar
        ->and(substr_count($salida, 'Difiere de 2025'))->toBe(1);
});

test('nunca cambia las tasas de una modalidad que ya existe', function () {
    $this->seed(ModalidadCreaSeeder::class);
    ModalidadCrea::where('nombre', 'Artesanal')->update(['tasa_moratoria' => 3]);

    Artisan::call('db:seed', ['--class' => ActualizarModalidadesSeeder::class]);

    $artesanal = ModalidadCrea::where('nombre', 'Artesanal')->sole();
    expect($artesanal->tasa_moratoria)->toBe('3.00')
        // Lo que estaba vacío sí se completa
        ->and($artesanal->monto_maximo)->toBe('25000.00')
        ->and(Artisan::output())->toContain('tasa_moratoria (hoy 3.00, 2025: 0.00)');
});
