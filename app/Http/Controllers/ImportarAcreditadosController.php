<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Services\PlantillaImportarAcreditados;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportarAcreditadosController extends Controller
{
    // Misma expresión que usa el portal ciudadano (Portal\WizardSolicitudController)
    private const CURP_REGEX = '/^[A-Z]{4}\d{6}[HM](AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/';

    // Públicas para que la plantilla (PlantillaImportarAcreditados) salga de aquí y no las repita
    public const COLUMNAS = [
        'nombre_completo', 'curp', 'rfc', 'municipio', 'sexo',
        'correo', 'direccion_fiscal', 'regimen', 'clave_personalizada',
    ];

    // Hoja de la plantilla donde van los datos
    public const HOJA = 'Acreditados';

    // Guía para el administrador; el repositorio es público, así que se puede abrir sin cuenta de GitHub
    public const GUIA = 'https://github.com/diegomtr8-art/iyem-crea/blob/develop/docs/IMPORTADOR_ACREDITADOS.md';

    public function index()
    {
        return Inertia::render('Admin/ImportarAcreditados', [
            'columnas'     => self::COLUMNAS,
            'vista_previa' => session('importacion_acreditados'),
            'guia'         => self::GUIA,
        ]);
    }

    public function plantilla(PlantillaImportarAcreditados $plantilla): StreamedResponse
    {
        $libro = $plantilla->generar();

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, 'plantilla_importar_acreditados.xlsx', [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function previsualizar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:2048',
        ], [
            'archivo.required' => 'Selecciona un archivo.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx o .xls).',
            'archivo.max'      => 'El archivo no puede pesar más de 2 MB.',
        ]);

        try {
            $libro = IOFactory::load($request->file('archivo')->getRealPath());

            // La plantilla también trae Instrucciones y Catálogos: si se guardó con otra hoja abierta,
            // los datos se siguen leyendo de la hoja Acreditados. Cualquier otro Excel: la hoja activa.
            $filas = ($libro->getSheetByName(self::HOJA) ?? $libro->getActiveSheet())
                ->toArray(null, true, false, false);
        } catch (\Exception) {
            return back()->with('error', 'No se pudo leer el archivo. Verifica que sea un Excel válido.');
        }

        // La primera fila son los encabezados: "Nombre completo" -> nombre_completo
        $encabezados = array_map(fn ($t) => Str::slug(trim((string) $t), '_'), array_shift($filas) ?? []);

        $faltantes = array_diff(['nombre_completo', 'municipio', 'sexo'], $encabezados);
        if ($faltantes) {
            return back()->with('error', 'Al archivo le faltan las columnas: ' . implode(', ', $faltantes));
        }

        $validas = [];
        $conErrores = [];
        $vistos = ['curp' => [], 'rfc' => []];

        foreach ($filas as $i => $fila) {
            $numero = $i + 2; // número de fila tal como se ve en Excel

            if (collect($fila)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue; // fila vacía
            }

            $registro = array_combine($encabezados, $fila);
            $datos = [];
            foreach (self::COLUMNAS as $col) {
                $valor = trim((string) ($registro[$col] ?? ''));
                $datos[$col] = $valor === '' ? null : $valor;
            }
            foreach (['curp', 'rfc', 'sexo'] as $col) {
                if ($datos[$col] !== null) {
                    $datos[$col] = mb_strtoupper($datos[$col]);
                }
            }

            $errores = Validator::make($datos, self::reglas(), self::mensajes(), self::atributos())
                ->errors()->all();

            // La tabla no acepta CURP ni RFC repetidos, tampoco dentro del mismo archivo
            foreach (['curp', 'rfc'] as $col) {
                $valor = $datos[$col];
                if ($valor === null) {
                    continue;
                }
                if (isset($vistos[$col][$valor])) {
                    $errores[] = strtoupper($col) . ": ya aparece en la fila {$vistos[$col][$valor]} del archivo.";
                } else {
                    $vistos[$col][$valor] = $numero;
                }
            }

            if ($errores) {
                $conErrores[] = ['fila' => $numero, 'nombre' => $datos['nombre_completo'], 'curp' => $datos['curp'], 'errores' => $errores];
            } else {
                $validas[] = ['fila' => $numero] + $datos;
            }
        }

        if (! $validas && ! $conErrores) {
            return back()->with('error', 'El archivo no tiene filas con datos.');
        }

        session(['importacion_acreditados' => [
            'archivo' => $request->file('archivo')->getClientOriginalName(),
            'validas' => $validas,
            'errores' => $conErrores,
        ]]);

        return redirect()->route('importar-acreditados.index');
    }

    public function confirmar()
    {
        // pull() lee y borra: si le dan doble clic, la segunda vez ya no hay nada que insertar
        $importacion = session()->pull('importacion_acreditados');

        if (empty($importacion['validas'])) {
            return redirect()->route('importar-acreditados.index')
                ->with('error', 'No hay filas válidas pendientes de importar.');
        }

        $insertados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($importacion, &$insertados, &$omitidos) {
            foreach ($importacion['validas'] as $fila) {
                // Por si alguien capturó a la misma persona entre la vista previa y la confirmación
                $yaExiste = ($fila['curp'] && Acreditado::where('curp', $fila['curp'])->exists())
                    || ($fila['rfc'] && Acreditado::where('rfc', $fila['rfc'])->exists());

                if ($yaExiste) {
                    $omitidos++;
                    continue;
                }

                Acreditado::create(Arr::except($fila, 'fila'));
                $insertados++;
            }
        });

        $mensaje = "Acreditados importados: {$insertados}.";
        if ($omitidos) {
            $mensaje .= " Omitidos porque ya existían: {$omitidos}.";
        }

        return redirect()->route('importar-acreditados.index')->with('success', $mensaje);
    }

    public function cancelar()
    {
        session()->forget('importacion_acreditados');

        return redirect()->route('importar-acreditados.index');
    }

    // Mismas reglas que AcreditadoController::store() para los datos del acreditado,
    // más el formato de CURP del portal y la revisión de duplicados (la tabla los tiene como unique).
    // Públicas para que la hoja Instrucciones de la plantilla muestre lo mismo que valida el importador.
    public static function reglas(): array
    {
        return [
            'nombre_completo'     => 'required|string|max:255',
            'municipio'           => 'required|string',
            'sexo'                => 'required|in:H,M',
            'rfc'                 => 'nullable|string|max:13|unique:acreditados,rfc',
            'curp'                => ['nullable', 'string', 'regex:' . self::CURP_REGEX, 'unique:acreditados,curp'],
            'correo'              => 'nullable|email',
            'direccion_fiscal'    => 'nullable|string|max:500',
            'regimen'             => 'nullable|string',
            'clave_personalizada' => 'nullable|string',
        ];
    }

    public static function mensajes(): array
    {
        return [
            'required'     => 'Falta :attribute.',
            'max'          => ':Attribute pasa de :max caracteres.',
            'sexo.in'      => 'El sexo debe ser H o M.',
            'curp.regex'   => 'La CURP no tiene un formato válido.',
            'curp.unique'  => 'Ya existe un acreditado con esta CURP.',
            'rfc.unique'   => 'Ya existe un acreditado con este RFC.',
            'correo.email' => 'El correo no es válido.',
        ];
    }

    public static function atributos(): array
    {
        return [
            'nombre_completo'  => 'el nombre completo',
            'municipio'        => 'el municipio',
            'sexo'             => 'el sexo',
            'rfc'              => 'el RFC',
            'direccion_fiscal' => 'la dirección fiscal',
        ];
    }
}