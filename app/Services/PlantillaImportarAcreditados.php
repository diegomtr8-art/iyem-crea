<?php

namespace App\Services;

use App\Http\Controllers\AcreditadoController;
use App\Http\Controllers\ImportarAcreditadosController as Importador;
use App\Http\Controllers\Portal\WizardSolicitudController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla de Excel para el importador de acreditados (ver docs/IMPORTADOR_ACREDITADOS.md).
 * Las columnas, lo obligatorio y los mensajes de error salen de ImportarAcreditadosController, y los
 * catálogos, de los que ya usa el sistema: si cambia el importador, la plantilla cambia con él.
 */
class PlantillaImportarAcreditados
{
    public const HOJA_INSTRUCCIONES = 'Instrucciones';
    public const HOJA_CATALOGOS = 'Catálogos';

    // Filas con listas desplegables
    private const FILAS = 1000;

    private const SEXOS = ['H' => 'Hombre', 'M' => 'Mujer'];

    // Inventadas: CURP y RFC no tienen formato válido, así que si se suben tal cual el importador las rechaza
    private const EJEMPLOS = [
        [
            'nombre_completo' => 'EJEMPLO, BORRAR - Ana Ficticia Uno', 'curp' => 'CURP-DE-EJEMPLO-1', 'rfc' => 'RFC-DE-EJEMPLO-1',
            'municipio' => 'Mérida', 'sexo' => 'M', 'correo' => 'ana.ficticia@example.com',
            'direccion_fiscal' => 'Calle Ficticia 123, Col. Ejemplo', 'regimen' => '626', 'clave_personalizada' => 'EJEMPLO-001',
        ],
        [
            'nombre_completo' => 'EJEMPLO, BORRAR - Luis Ficticio Dos', 'curp' => 'CURP-DE-EJEMPLO-2', 'rfc' => 'RFC-DE-EJEMPLO-2',
            'municipio' => 'Valladolid', 'sexo' => 'H', 'correo' => 'luis.ficticio@example.com',
            'direccion_fiscal' => 'Calle Inventada 45, Col. Muestra', 'regimen' => '612', 'clave_personalizada' => 'EJEMPLO-002',
        ],
    ];

    // Lo que no sale de las reglas. En "errores" va un valor mal escrito: el mensaje lo da el propio importador.
    private const AYUDA = [
        'nombre_completo' => [
            'formato' => 'Nombre(s) y apellidos, como en su identificación.',
            'ejemplo' => 'Ana Ficticia Ejemplo',
            'errores' => [[null, 'la celda está vacía']],
        ],
        'curp' => [
            'formato' => '18 caracteres: 4 letras, fecha de nacimiento (AAMMDD), H o M, 2 letras del estado (YN es Yucatán), 3 consonantes, 1 letra o número y 1 número. Puede ir en minúsculas: se guarda en mayúsculas. No puede repetirse.',
            'ejemplo' => 'AAAA000000HYNBBB00 (solo muestra la forma)',
            'errores' => [['AAAA000000HYNBBB0', 'le falta o le sobra un carácter, tiene espacios o una letra O en lugar de un cero']],
        ],
        'rfc' => [
            'formato' => 'RFC con homoclave: 13 caracteres para persona física y 12 para persona moral. Puede ir en minúsculas: se guarda en mayúsculas. No puede repetirse. El importador no revisa el formato: cópialo tal como viene en la constancia del SAT.',
            'ejemplo' => 'AAAA000000AA0 (solo muestra la forma)',
            'errores' => [['AAAA-000000-AA0', 'tiene guiones o espacios']],
        ],
        'municipio' => [
            'formato' => 'Municipio de Yucatán, escrito como en la hoja Catálogos (con acentos). Elígelo de la lista de la celda.',
            'ejemplo' => 'Mérida',
            'errores' => [[null, 'la celda está vacía']],
            'nota' => 'El importador no marca error si el municipio está mal escrito (por ejemplo «Merida»): se guarda así y sale aparte en los reportes por municipio.',
        ],
        'sexo' => [
            'formato' => 'H (hombre) o M (mujer). Puede ir en minúscula.',
            'ejemplo' => 'M',
            'errores' => [[null, 'la celda está vacía'], ['Mujer', 'se escribió la palabra completa en lugar de H o M']],
        ],
        'correo' => [
            'formato' => 'Correo electrónico del acreditado.',
            'ejemplo' => 'nombre@example.com',
            'errores' => [['nombre.example.com', 'le falta la @, tiene espacios o una coma en lugar de un punto']],
        ],
        'direccion_fiscal' => [
            'formato' => 'Calle, número, colonia y municipio, como en la constancia del SAT.',
            'ejemplo' => 'Calle Ficticia 123, Col. Ejemplo',
            'errores' => [],
        ],
        'regimen' => [
            'formato' => 'Código de 3 números del régimen fiscal del SAT (hoja Catálogos). Es el mismo código que guarda el formulario de alta. Elígelo de la lista de la celda.',
            'ejemplo' => '626',
            'errores' => [],
            'nota' => 'El importador no marca error si se escribe el nombre del régimen en lugar del código: se guarda así y el formulario de edición no lo reconoce.',
        ],
        'clave_personalizada' => [
            'formato' => 'Clave de pago del acreditado, si tiene una. Si empieza con ceros, no se pierden: la columna ya es de texto.',
            'ejemplo' => 'CREA-001',
            'errores' => [],
        ],
    ];

    public function generar(): Spreadsheet
    {
        $libro = new Spreadsheet();

        $this->hojaAcreditados($libro->getActiveSheet()->setTitle(Importador::HOJA));
        $this->hojaInstrucciones($libro->createSheet()->setTitle(self::HOJA_INSTRUCCIONES));
        $this->hojaCatalogos($libro->createSheet()->setTitle(self::HOJA_CATALOGOS));

        // Se abre en la hoja de los datos
        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    private function hojaAcreditados(Worksheet $hoja): void
    {
        $ultima = $this->letra(last(Importador::COLUMNAS));

        $this->encabezado($hoja, Importador::COLUMNAS);

        // Los obligatorios en rojo, como dice la hoja Instrucciones
        foreach (Importador::COLUMNAS as $columna) {
            if ($this->esObligatoria($columna)) {
                $hoja->getStyle($this->letra($columna) . '1')->getFill()->getStartColor()->setRGB('B91C1C');
            }
        }

        foreach (self::EJEMPLOS as $i => $ejemplo) {
            foreach (Importador::COLUMNAS as $columna) {
                $this->texto($hoja, $this->letra($columna) . ($i + 2), $ejemplo[$columna] ?? '');
            }
        }
        $hoja->getStyle('A2:' . $ultima . (count(self::EJEMPLOS) + 1))->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF4CC');

        // Columnas completas como texto, para que Excel no quite ceros a la izquierda ni convierta claves en números
        // o fechas. Se aplica a la columna y no celda por celda: así no se crean 1000 filas vacías que leer.
        $hoja->getStyle("A:{$ultima}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $municipios = count(WizardSolicitudController::MUNICIPIOS_YUCATAN);
        $regimenes  = count(AcreditadoController::regimenesFiscalesSat());
        $catalogo   = "'" . self::HOJA_CATALOGOS . "'!";

        $this->lista($hoja, 'municipio', "{$catalogo}\$A\$2:\$A\$" . ($municipios + 1), false,
            'Ese municipio no está en la hoja Catálogos. Revisa cómo se escribe (con acentos).');
        $this->lista($hoja, 'sexo', "{$catalogo}\$C\$2:\$C\$" . (count($this->sexos()) + 1), true,
            'El sexo debe ser H o M.');
        $this->lista($hoja, 'regimen', "{$catalogo}\$F\$2:\$F\$" . ($regimenes + 1), false,
            'Escribe el código de 3 números del régimen, como en la hoja Catálogos.');

        foreach (['nombre_completo' => 36, 'curp' => 22, 'rfc' => 18, 'municipio' => 20, 'sexo' => 8, 'correo' => 30,
                  'direccion_fiscal' => 40, 'regimen' => 12, 'clave_personalizada' => 20] as $columna => $ancho) {
            if (in_array($columna, Importador::COLUMNAS, true)) {
                $hoja->getColumnDimension($this->letra($columna))->setWidth($ancho);
            }
        }
    }

    private function hojaInstrucciones(Worksheet $hoja): void
    {
        $hoja->setCellValue('A1', 'Cómo llenar la plantilla para importar acreditados');
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $hoja->fromArray([
            ['1. Llena la hoja ' . Importador::HOJA . ': un acreditado por fila, desde la fila 2.'],
            ['2. Borra las 2 filas amarillas que dicen «EJEMPLO, BORRAR». Si se quedan, el importador las rechaza.'],
            ['3. No cambies, muevas ni borres los encabezados de la fila 1. Los que están en rojo son obligatorios.'],
            ['4. Municipio, sexo y régimen: elígelos de la lista de la celda o escríbelos igual que en la hoja ' . self::HOJA_CATALOGOS . '.'],
            ['5. Guarda el archivo como Excel (.xlsx), de máximo 2 MB, y súbelo en Importar acreditados. Nada se guarda hasta que revises la vista previa y confirmes.'],
            ['Guía paso a paso: ' . Importador::GUIA],
        ], null, 'A3');
        $hoja->getCell('A8')->getHyperlink()->setUrl(Importador::GUIA);

        $this->encabezado($hoja, ['Columna', 'Obligatoria', 'Formato', 'Ejemplo', 'Errores comunes'], 10, false);

        $fila = 11;
        foreach (Importador::COLUMNAS as $columna) {
            $ayuda = self::AYUDA[$columna] ?? ['formato' => '', 'ejemplo' => '', 'errores' => []];
            $maximo = $this->maximo($columna);

            $hoja->fromArray([[
                $columna,
                $this->esObligatoria($columna) ? 'Sí' : 'No',
                trim($ayuda['formato'] . ($maximo ? " Máximo {$maximo} caracteres." : '')),
                $ayuda['ejemplo'],
                $this->erroresComunes($columna, $ayuda),
            ]], null, "A{$fila}", true);
            $fila++;
        }

        $hoja->getStyle('A11:E' . ($fila - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        foreach (['A' => 22, 'B' => 12, 'C' => 60, 'D' => 30, 'E' => 70] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }
    }

    private function hojaCatalogos(Worksheet $hoja): void
    {
        $this->encabezado($hoja, ['Municipio', null, 'Sexo', 'Significado', null, 'Régimen (código)', 'Nombre']);

        $hoja->fromArray(array_map(fn ($m) => [$m], WizardSolicitudController::MUNICIPIOS_YUCATAN), null, 'A2', true);
        $hoja->fromArray(array_map(fn ($s) => [$s, self::SEXOS[$s] ?? $s], $this->sexos()), null, 'C2', true);
        foreach (AcreditadoController::regimenesFiscalesSat() as $i => $regimen) {
            $this->texto($hoja, 'F' . ($i + 2), $regimen['id']);
            $this->texto($hoja, 'G' . ($i + 2), $regimen['nombre']);
        }

        foreach (['A' => 24, 'B' => 3, 'C' => 8, 'D' => 14, 'E' => 3, 'F' => 18, 'G' => 70] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }
    }

    /** Los mensajes que daría el importador con cada valor mal escrito, más los de duplicados. */
    private function erroresComunes(string $columna, array $ayuda): string
    {
        $lineas = [];

        foreach ($ayuda['errores'] as [$valor, $causa]) {
            $mensaje = Validator::make([$columna => $valor], [$columna => Importador::reglas()[$columna]],
                Importador::mensajes(), Importador::atributos())->errors()->first($columna);
            $lineas[] = "«{$mensaje}»: {$causa}.";
        }

        if (in_array($columna, ['curp', 'rfc'], true)) {
            $clave = Str::upper($columna);
            $misma = $columna === 'curp' ? 'la misma CURP' : 'el mismo RFC';
            $lineas[] = '«' . Importador::mensajes()["{$columna}.unique"] . '»: esa persona ya está dada de alta en el sistema.';
            $lineas[] = "«{$clave}: ya aparece en la fila N del archivo.»: {$misma} está en dos filas del archivo.";
        }

        if (isset($ayuda['nota'])) {
            $lineas[] = $ayuda['nota'];
        }

        return implode("\n", $lineas);
    }

    // Explícito como texto: si no, PhpSpreadsheet guarda "626" como número
    private function texto(Worksheet $hoja, string $celda, string $valor): void
    {
        $hoja->setCellValueExplicit($celda, $valor, DataType::TYPE_STRING);
    }

    private function lista(Worksheet $hoja, string $columna, string $origen, bool $estricta, string $error): void
    {
        $letra = $this->letra($columna);

        $validacion = (new DataValidation())
            ->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle($estricta ? DataValidation::STYLE_STOP : DataValidation::STYLE_WARNING)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Fuera del catálogo')
            ->setError($error)
            ->setFormula1($origen);

        $hoja->setDataValidation("{$letra}2:{$letra}" . (self::FILAS + 1), $validacion);
    }

    private function encabezado(Worksheet $hoja, array $titulos, int $fila = 1, bool $congelar = true): void
    {
        $hoja->fromArray($titulos, null, "A{$fila}", true);

        foreach ($titulos as $i => $titulo) {
            if ($titulo === null) {
                continue;
            }
            $hoja->getStyle(Coordinate::stringFromColumnIndex($i + 1) . $fila)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            ]);
        }

        if ($congelar) {
            $hoja->freezePane('A' . ($fila + 1));
        }
    }

    /** Los valores de la regla in: del sexo (hoy H y M). */
    private function sexos(): array
    {
        $regla = collect($this->partesDeRegla('sexo'))->first(fn ($parte) => str_starts_with($parte, 'in:'));

        return explode(',', Str::after($regla, 'in:'));
    }

    private function esObligatoria(string $columna): bool
    {
        return in_array('required', $this->partesDeRegla($columna), true);
    }

    private function maximo(string $columna): ?int
    {
        $regla = collect($this->partesDeRegla($columna))->first(fn ($parte) => str_starts_with($parte, 'max:'));

        return $regla ? (int) Str::after($regla, 'max:') : null;
    }

    private function partesDeRegla(string $columna): array
    {
        $regla = Importador::reglas()[$columna] ?? [];

        return is_array($regla) ? array_filter($regla, 'is_string') : explode('|', $regla);
    }

    private function letra(string $columna): string
    {
        return Coordinate::stringFromColumnIndex(array_search($columna, Importador::COLUMNAS, true) + 1);
    }
}
