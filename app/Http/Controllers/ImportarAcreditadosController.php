<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarAcreditadosController extends Controller
{
    // Misma expresión que usa el portal ciudadano (Portal\WizardSolicitudController)
    private const CURP_REGEX = '/^[A-Z]{4}\d{6}[HM](AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/';

    private const COLUMNAS = [
        'nombre_completo', 'curp', 'rfc', 'municipio', 'sexo',
        'correo', 'direccion_fiscal', 'regimen', 'clave_personalizada',
    ];

    public function index()
    {
        return Inertia::render('Admin/ImportarAcreditados', [
            'columnas'     => self::COLUMNAS,
            'vista_previa' => session('importacion_acreditados'),
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
            $filas = IOFactory::load($request->file('archivo')->getRealPath())
                ->getActiveSheet()
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

            $errores = Validator::make($datos, $this->reglas(), $this->mensajes(), $this->atributos())
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
    private function reglas(): array
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

    private function mensajes(): array
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

    private function atributos(): array
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