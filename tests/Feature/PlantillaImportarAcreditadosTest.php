<?php

use App\Http\Controllers\AcreditadoController;
use App\Http\Controllers\ImportarAcreditadosController;
use App\Http\Controllers\Portal\WizardSolicitudController;
use App\Models\Acreditado;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

/** Descarga la plantilla como lo haría el administrador y la abre. */
function plantillaDescargada(): Spreadsheet
{
    $respuesta = test()->get(route('importar-acreditados.plantilla'))
        ->assertOk()
        ->assertDownload('plantilla_importar_acreditados.xlsx');

    $ruta = tempnam(sys_get_temp_dir(), 'plantilla');
    file_put_contents($ruta, $respuesta->streamedContent());
    $libro = IOFactory::load($ruta);
    unlink($ruta);

    // PhpSpreadsheet no libera la memoria solo; sin esto las pruebas se quedan sin memoria
    test()->beforeApplicationDestroyed(fn () => $libro->disconnectWorksheets());

    return $libro;
}

/** Guarda la plantilla como .xlsx y la sube a la vista previa del importador. */
function subirPlantilla(Spreadsheet $libro): void
{
    $ruta = tempnam(sys_get_temp_dir(), 'plantilla');
    (new Xlsx($libro))->save($ruta);
    $archivo = UploadedFile::fake()->createWithContent('plantilla_importar_acreditados.xlsx', file_get_contents($ruta));
    unlink($ruta);

    test()->post(route('importar-acreditados.previsualizar'), ['archivo' => $archivo])
        ->assertRedirect(route('importar-acreditados.index'));
}

function filasDeLaHoja(Spreadsheet $libro, string $hoja): array
{
    return $libro->getSheetByName($hoja)->toArray(null, true, false, false);
}

beforeEach(function () {
    Role::create(['name' => 'Administrador']);

    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');
    $this->actingAs($admin);
});

test('la plantilla trae exactamente los encabezados que exige el importador', function () {
    $libro = plantillaDescargada();
    $encabezados = filasDeLaHoja($libro, 'Acreditados')[0];

    expect($libro->getSheetNames())->toBe(['Acreditados', 'Instrucciones', 'Catálogos'])
        ->and($libro->getActiveSheet()->getTitle())->toBe('Acreditados')
        ->and($encabezados)->toBe(ImportarAcreditadosController::COLUMNAS)
        // El importador normaliza los encabezados con Str::slug: estos llegan sin cambios
        ->and(array_map(fn ($t) => Str::slug(trim($t), '_'), $encabezados))->toBe(ImportarAcreditadosController::COLUMNAS)
        ->and($encabezados)->toContain('nombre_completo', 'municipio', 'sexo');
});

test('las 2 filas de ejemplo son claramente falsas y si se suben tal cual no crean acreditados', function () {
    $libro = plantillaDescargada();
    $ejemplos = array_slice(filasDeLaHoja($libro, 'Acreditados'), 1, 2);

    expect(array_column($ejemplos, 0))->each->toStartWith('EJEMPLO, BORRAR');

    subirPlantilla($libro);

    $vista = session('importacion_acreditados');
    expect($vista['validas'])->toBe([])
        ->and(array_column($vista['errores'], 'fila'))->toBe([2, 3]);
    foreach ($vista['errores'] as $fila) {
        expect($fila['errores'])->toContain('La CURP no tiene un formato válido.', 'El RFC pasa de 13 caracteres.');
    }

    $this->post(route('importar-acreditados.confirmar'))
        ->assertSessionHas('error', 'No hay filas válidas pendientes de importar.');
    expect(Acreditado::count())->toBe(0);
});

test('si a una fila de ejemplo solo le borran la CURP, el RFC de ejemplo la sigue frenando', function () {
    $libro = plantillaDescargada();
    $libro->getSheetByName('Acreditados')->setCellValue('B2', null)->setCellValue('B3', null);

    subirPlantilla($libro);

    expect(session('importacion_acreditados.validas'))->toBe([])
        ->and(array_column(session('importacion_acreditados.errores'), 'errores'))
        ->each->toBe(['El RFC pasa de 13 caracteres.']);
});

test('una plantilla llenada con datos válidos pasa la vista previa sin errores y se importa', function () {
    $libro = plantillaDescargada();
    $hoja = $libro->getSheetByName('Acreditados');
    $hoja->removeRow(2, 2);

    // Datos ficticios de prueba, con minúsculas y celdas opcionales vacías, como los capturaría alguien
    $hoja->fromArray([
        ['Ana Prueba Uno', 'PEGJ900101MYNRRN01', 'PEGJ900101AB1', 'Mérida', 'M', 'ana@prueba.test', 'Calle Ficticia 1', '626', '0001'],
        ['Luis Prueba Dos', 'pegj900102hynrrn02', 'pegj900102ab2', 'Valladolid', 'h', null, null, '612', null],
    ], null, 'A2', true);
    // El administrador guardó el archivo mientras veía las instrucciones
    $libro->setActiveSheetIndexByName('Instrucciones');

    subirPlantilla($libro);

    expect(session('importacion_acreditados.errores'))->toBe([])
        ->and(array_column(session('importacion_acreditados.validas'), 'fila'))->toBe([2, 3]);

    $this->post(route('importar-acreditados.confirmar'))->assertSessionHas('success', 'Acreditados importados: 2.');

    expect(Acreditado::where('curp', 'PEGJ900101MYNRRN01')->first()->only(['nombre_completo', 'municipio', 'sexo', 'regimen', 'clave_personalizada']))
        ->toBe(['nombre_completo' => 'Ana Prueba Uno', 'municipio' => 'Mérida', 'sexo' => 'M', 'regimen' => '626', 'clave_personalizada' => '0001'])
        ->and(Acreditado::where('curp', 'PEGJ900102HYNRRN02')->value('sexo'))->toBe('H');
});

test('la hoja Catálogos sale de los catálogos del sistema y las celdas ofrecen esas listas', function () {
    $libro = plantillaDescargada();
    $catalogos = array_slice(filasDeLaHoja($libro, 'Catálogos'), 1);
    $columna = fn (int $i) => array_values(array_filter(array_column($catalogos, $i), fn ($v) => $v !== null));

    expect($columna(0))->toBe(WizardSolicitudController::MUNICIPIOS_YUCATAN)
        ->and($columna(2))->toBe(['H', 'M'])
        ->and($columna(5))->toBe(array_column(AcreditadoController::regimenesFiscalesSat(), 'id'));

    $listas = collect($libro->getSheetByName('Acreditados')->getDataValidationCollection())
        ->map(fn ($v) => $v->getFormula1());

    expect($listas->all())->toBe([
        'D2:D1001' => "'Catálogos'!\$A\$2:\$A\$107",
        'E2:E1001' => "'Catálogos'!\$C\$2:\$C\$3",
        'H2:H1001' => "'Catálogos'!\$F\$2:\$F\$24",
    ]);

    // Los ejemplos usan valores que están en los catálogos
    $ejemplos = array_slice(filasDeLaHoja($libro, 'Acreditados'), 1, 2);
    expect(array_diff(array_column($ejemplos, 3), $columna(0)))->toBe([])
        ->and(array_diff(array_column($ejemplos, 7), $columna(5)))->toBe([]);
});

test('la hoja Instrucciones explica cada columna con lo mismo que valida el importador', function () {
    $filas = filasDeLaHoja(plantillaDescargada(), 'Instrucciones');
    $tabla = collect(array_slice($filas, 10))->filter(fn ($f) => $f[0] !== null)->keyBy(0);
    $errores = $tabla->pluck(4)->implode("\n");

    expect($tabla->keys()->all())->toBe(ImportarAcreditadosController::COLUMNAS)
        ->and($tabla->filter(fn ($f) => $f[1] === 'Sí')->keys()->all())->toBe(['nombre_completo', 'municipio', 'sexo'])
        ->and($tabla['nombre_completo'][2])->toContain('Máximo 255 caracteres.')
        ->and($tabla['rfc'][2])->toContain('Máximo 13 caracteres.')
        ->and($errores)->toContain(
            '«Falta el nombre completo.»', '«La CURP no tiene un formato válido.»', '«Ya existe un acreditado con esta CURP.»',
            '«El RFC pasa de 13 caracteres.»', '«Falta el municipio.»', '«El sexo debe ser H o M.»', '«El correo no es válido.»',
        )
        // Si cambia una regla y el valor de ejemplo ya no da error, aquí quedaría un mensaje vacío
        ->and($errores)->not->toContain('«»')
        ->and(collect($filas)->flatten()->implode(' '))->toContain(ImportarAcreditadosController::GUIA);
});

test('la pantalla del importador ofrece la plantilla y la guía', function () {
    $this->withoutVite()->get(route('importar-acreditados.index'))
        ->assertInertia(fn (Assert $page) => $page->where('guia', ImportarAcreditadosController::GUIA));
});

test('un usuario sin rol Administrador recibe 403 al pedir la plantilla', function () {
    $this->actingAs(User::factory()->create(['tipo' => 'operativo']))
        ->get(route('importar-acreditados.plantilla'))
        ->assertForbidden();
});
