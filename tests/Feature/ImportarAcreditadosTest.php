<?php

use App\Models\Acreditado;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

/**
 * Una fila válida del Excel, con los encabezados como los escribiría el administrador.
 * $n hace únicos el nombre, la CURP y el RFC. Datos ficticios.
 */
function filaDeAcreditado(int $n, array $cambios = []): array
{
    return array_merge([
        'Nombre completo' => "Acreditado de Prueba {$n}",
        'CURP'            => sprintf('PEGJ9001%02dHYNRRN01', $n),
        'RFC'             => sprintf('PEGJ9001%02dAB1', $n),
        'Municipio'       => 'Mérida',
        'Sexo'            => 'H',
        'Correo'          => "acreditado{$n}@prueba.test",
    ], $cambios);
}

/**
 * Arma un .xlsx real con PhpSpreadsheet: primera fila de encabezados y una fila por acreditado.
 * Una fila [] queda vacía en el Excel.
 */
function excelDeAcreditados(array $filas, ?array $encabezados = null): UploadedFile
{
    $libro = new Spreadsheet();
    $libro->getActiveSheet()->fromArray(
        [$encabezados ?? array_keys(filaDeAcreditado(1)), ...array_map('array_values', $filas)],
        null, 'A1', true
    );

    $ruta = tempnam(sys_get_temp_dir(), 'crea');
    (new Xlsx($libro))->save($ruta);
    $contenido = file_get_contents($ruta);
    unlink($ruta);

    return UploadedFile::fake()->createWithContent('acreditados.xlsx', $contenido);
}

// Los errores de la vista previa como [fila de Excel => motivos]
function motivosPorFila(array $vistaPrevia): array
{
    return array_column($vistaPrevia['errores'], 'errores', 'fila');
}

beforeEach(function () {
    Role::create(['name' => 'Administrador']);

    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');
    $this->actingAs($admin);
});

test('un Excel válido muestra la vista previa sin guardar nada y al confirmar importa todas las filas', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1),
        // Capturada en minúsculas, con espacios y sin correo
        filaDeAcreditado(2, ['Nombre completo' => '  Acreditado de Prueba 2 ', 'CURP' => 'pegj900102mynrrn01', 'RFC' => 'pegj900102ab1', 'Municipio' => 'Progreso', 'Sexo' => 'm', 'Correo' => '']),
    ])])->assertRedirect(route('importar-acreditados.index'));

    $vista = session('importacion_acreditados');
    expect(array_column($vista['validas'], 'fila'))->toBe([2, 3])
        ->and($vista['errores'])->toBe([])
        ->and(Acreditado::count())->toBe(0); // la vista previa no guarda nada

    $this->post(route('importar-acreditados.confirmar'))
        ->assertRedirect(route('importar-acreditados.index'))
        ->assertSessionHas('success', 'Acreditados importados: 2.');

    expect(Acreditado::count())->toBe(2);
    $this->assertDatabaseHas('acreditados', [
        'nombre_completo' => 'Acreditado de Prueba 1', 'curp' => 'PEGJ900101HYNRRN01', 'rfc' => 'PEGJ900101AB1',
        'municipio' => 'Mérida', 'sexo' => 'H', 'correo' => 'acreditado1@prueba.test',
    ]);
    $this->assertDatabaseHas('acreditados', [
        'nombre_completo' => 'Acreditado de Prueba 2', 'curp' => 'PEGJ900102MYNRRN01', 'rfc' => 'PEGJ900102AB1',
        'municipio' => 'Progreso', 'sexo' => 'M', 'correo' => null,
    ]);

    // Un segundo clic en confirmar ya no inserta nada
    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('error', 'No hay filas válidas pendientes de importar.');
    expect(Acreditado::count())->toBe(2);
});

test('si al Excel le faltan columnas obligatorias no hay vista previa', function (array $quitar, string $faltantes) {
    $fila = Arr::except(filaDeAcreditado(1), $quitar);

    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([$fila], array_keys($fila))])
        ->assertSessionHas('error', "Al archivo le faltan las columnas: {$faltantes}");

    expect(session('importacion_acreditados'))->toBeNull()
        ->and(Acreditado::count())->toBe(0);
})->with([
    'sin nombre completo'    => [['Nombre completo'], 'nombre_completo'],
    'sin municipio'          => [['Municipio'], 'municipio'],
    'sin sexo'               => [['Sexo'], 'sexo'],
    'sin municipio ni sexo'  => [['Municipio', 'Sexo'], 'municipio, sexo'],
]);

test('una fila con la columna pero sin el dato obligatorio se marca con lo que falta', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1, ['Nombre completo' => '']), // fila 2
        filaDeAcreditado(2, ['Municipio' => '']),       // fila 3
        filaDeAcreditado(3, ['Sexo' => '']),            // fila 4
        filaDeAcreditado(4),                            // fila 5
    ])]);

    $vista = session('importacion_acreditados');
    expect(motivosPorFila($vista))->toBe([
        2 => ['Falta el nombre completo.'],
        3 => ['Falta el municipio.'],
        4 => ['Falta el sexo.'],
    ])->and(array_column($vista['validas'], 'fila'))->toBe([5]);
});

test('una CURP o un RFC repetido dentro del archivo se marca en la fila que lo repite', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1),                                   // fila 2
        filaDeAcreditado(2, ['CURP' => 'pegj900101hynrrn01']), // fila 3: la CURP de la fila 2, en minúsculas
        filaDeAcreditado(3, ['RFC' => 'PEGJ900101AB1']),       // fila 4: el RFC de la fila 2
        filaDeAcreditado(4),                                   // fila 5
    ])]);

    $vista = session('importacion_acreditados');
    expect(motivosPorFila($vista))->toBe([
        3 => ['CURP: ya aparece en la fila 2 del archivo.'],
        4 => ['RFC: ya aparece en la fila 2 del archivo.'],
    ])->and(array_column($vista['validas'], 'fila'))->toBe([2, 5]);

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('success', 'Acreditados importados: 2.');
    expect(Acreditado::orderBy('id')->pluck('nombre_completo')->all())
        ->toBe(['Acreditado de Prueba 1', 'Acreditado de Prueba 4']);
});

test('una CURP o un RFC que ya existe en la base se marca y no se importa', function () {
    Acreditado::create(['nombre_completo' => 'Capturado a mano 1', 'municipio' => 'Mérida', 'curp' => 'PEGJ900101HYNRRN01']);
    Acreditado::create(['nombre_completo' => 'Capturado a mano 2', 'municipio' => 'Mérida', 'rfc' => 'PEGJ900102AB1']);

    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1),                              // fila 2: su CURP ya está en la base
        filaDeAcreditado(2, ['RFC' => 'pegj900102ab1']),  // fila 3: su RFC ya está, aunque venga en minúsculas
        filaDeAcreditado(3),                              // fila 4
    ])]);

    $vista = session('importacion_acreditados');
    expect(motivosPorFila($vista))->toBe([
        2 => ['Ya existe un acreditado con esta CURP.'],
        3 => ['Ya existe un acreditado con este RFC.'],
    ])->and(array_column($vista['validas'], 'fila'))->toBe([4]);

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('success', 'Acreditados importados: 1.');
    expect(Acreditado::count())->toBe(3);
    $this->assertDatabaseHas('acreditados', ['nombre_completo' => 'Acreditado de Prueba 3']);
});

test('si alguien captura a la misma persona entre la vista previa y la confirmación, se omite', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1),
        filaDeAcreditado(2),
    ])]);
    expect(session('importacion_acreditados')['validas'])->toHaveCount(2);

    // Mientras el administrador revisa la vista previa, otra persona captura al acreditado 2 a mano
    Acreditado::create(['nombre_completo' => 'Capturado a mano', 'municipio' => 'Mérida', 'curp' => 'PEGJ900102HYNRRN01']);

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('success', 'Acreditados importados: 1. Omitidos porque ya existían: 1.');
    expect(Acreditado::count())->toBe(2);
    $this->assertDatabaseMissing('acreditados', ['nombre_completo' => 'Acreditado de Prueba 2']);
});

test('las filas vacías se ignoran y no cambian el número de fila de los errores', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        filaDeAcreditado(1),                                    // fila 2
        [],                                                     // fila 3: vacía
        array_fill(0, 6, '   '),                                // fila 4: solo espacios
        filaDeAcreditado(2, ['CURP' => 'NO ES UNA CURP']),      // fila 5
        filaDeAcreditado(3),                                    // fila 6
        [],                                                     // fila 7: vacía
        array_fill(0, 6, ' '),                                  // fila 8: vacía al final
    ])]);

    $vista = session('importacion_acreditados');
    expect(array_column($vista['validas'], 'fila'))->toBe([2, 6])
        ->and(motivosPorFila($vista))->toBe([5 => ['La CURP no tiene un formato válido.']]);

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('success', 'Acreditados importados: 2.');
    expect(Acreditado::count())->toBe(2);
});

test('un Excel con encabezados y solo filas vacías no genera vista previa', function () {
    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([
        [],
        array_fill(0, 6, '  '),
    ])])->assertSessionHas('error', 'El archivo no tiene filas con datos.');

    expect(session('importacion_acreditados'))->toBeNull();
});

test('con 10 filas y 2 CURP mal formadas, la vista previa dice cuáles y se guardan las 8 buenas', function () {
    $filas = array_map(fn ($n) => filaDeAcreditado($n), range(1, 10));
    $filas[3]['CURP'] = 'PEGJ900104XYNRRN01'; // fila 5: X en lugar de H o M
    $filas[7]['CURP'] = 'PEGJ900108HYNRRN0';  // fila 9: le falta un carácter

    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados($filas)]);

    $vista = session('importacion_acreditados');
    expect(motivosPorFila($vista))->toBe([
        5 => ['La CURP no tiene un formato válido.'],
        9 => ['La CURP no tiene un formato válido.'],
    ])->and($vista['validas'])->toHaveCount(8);

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('success', 'Acreditados importados: 8.');
    expect(Acreditado::count())->toBe(8);
    $this->assertDatabaseMissing('acreditados', ['nombre_completo' => 'Acreditado de Prueba 4']);
    $this->assertDatabaseMissing('acreditados', ['nombre_completo' => 'Acreditado de Prueba 8']);
});

test('un usuario que no es Administrador no puede importar', function () {
    $this->actingAs(User::factory()->create(['tipo' => 'operativo']));

    $this->post(route('importar-acreditados.previsualizar'), ['archivo' => excelDeAcreditados([filaDeAcreditado(1)])])
        ->assertForbidden();
    $this->post(route('importar-acreditados.confirmar'))->assertForbidden();

    expect(Acreditado::count())->toBe(0);
});
