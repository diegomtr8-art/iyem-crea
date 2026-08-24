<?php

namespace App\Services;

use App\Models\SolicitudCredito;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FormatosService
{
    /**
     * Genera el ZIP con todos los PDFs de la solicitud y lo guarda en disco local.
     * Devuelve la ruta relativa del ZIP generado. Si un formato individual falla,
     * se omite (y se registra en el log) para no bloquear la generación del resto.
     */
    public static function generarZip(SolicitudCredito $solicitud): string
    {
        $archivosTemp = [];
        foreach (self::mapaPdfs($solicitud) as $item) {
            try {
                // Instancia nueva de dompdf.wrapper en cada iteración: el wrapper
                // guarda el HTML y el render como estado interno, así que reutilizar
                // la misma instancia entre PDFs produce archivos corruptos a partir
                // del segundo (solo el primero queda bien formado).
                $dompdf = app('dompdf.wrapper');
                $dompdf->setPaper('letter', 'portrait');
                $archivosTemp[] = self::pdf($dompdf, $item['view'], $item['data'], $item['nombre'], $solicitud->id);
            } catch (\Throwable $e) {
                Log::warning("FormatosService: no se pudo generar el formato '{$item['tipo']}' de la solicitud {$solicitud->id}: {$e->getMessage()}");
            }
        }

        // Empaquetar ZIP
        $zipNombre  = "CREA_Formatos_Solicitud_{$solicitud->id}.zip";
        $zipRuta    = "solicitudes/{$solicitud->id}/formatos/{$zipNombre}";
        $zipRutaAbs = Storage::disk('local')->path($zipRuta);

        Storage::disk('local')->makeDirectory("solicitudes/{$solicitud->id}/formatos");

        $zip = new ZipArchive();
        if ($zip->open($zipRutaAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        foreach ($archivosTemp as [$nombreArchivo, $rutaLocal]) {
            $zip->addFile(Storage::disk('local')->path($rutaLocal), $nombreArchivo);
        }
        $zip->close();

        foreach ($archivosTemp as [$_, $rutaLocal]) {
            Storage::disk('local')->delete($rutaLocal);
        }

        return $zipRuta;
    }

    /**
     * Genera un único PDF (identificado por $tipo) y devuelve su contenido binario,
     * para descarga directa sin pasar por el ZIP.
     */
    public static function generarPdfIndividual(SolicitudCredito $solicitud, string $tipo): string
    {
        $dompdf = app('dompdf.wrapper');
        $dompdf->setPaper('letter', 'portrait');

        $item = collect(self::mapaPdfs($solicitud))->firstWhere('tipo', $tipo);

        if (!$item) {
            throw new \InvalidArgumentException("Tipo de formato desconocido: {$tipo}");
        }

        $dompdf->loadView($item['view'], $item['data']);

        return $dompdf->output();
    }

    /**
     * Catálogo ordenado de los formatos PDF de una solicitud: vista, datos y nombre
     * de archivo, condicionado por modalidad / tipo de persona / tipo de garantía.
     */
    private static function mapaPdfs(SolicitudCredito $solicitud): array
    {
        $solicitud->loadMissing(['modalidad', 'aval']);

        $modalidadNombre  = strtolower($solicitud->modalidad?->nombre ?? 'emprendedores');
        $isArtesanal      = str_contains($modalidadNombre, 'artesanal');
        $tipoPf           = $solicitud->tipo_persona === 'fisica';
        $conAval          = $solicitud->tipo_garantia === 'aval';
        $esEmprendimiento = (bool) $solicitud->es_emprendimiento;
        $wizard           = $solicitud->datos_wizard ?? [];

        $templateData = [
            'solicitud' => $solicitud,
            'wizard'    => $wizard,
            'aval'      => $solicitud->aval,
            'modalidad' => $solicitud->modalidad,
            'fecha'     => now()->format('d/m/Y'),
        ];

        $mapa = [];

        // Solicitud de Crédito
        if ($isArtesanal) {
            $mapa[] = ['tipo' => 'solicitud', 'view' => 'pdf.formatos.solicitud-pf-artesanal', 'data' => $templateData, 'nombre' => 'F-PR-OCC-01_Solicitud.pdf'];
        } elseif ($tipoPf) {
            $mapa[] = ['tipo' => 'solicitud', 'view' => 'pdf.formatos.solicitud-pf', 'data' => $templateData, 'nombre' => 'F-PR-OCC-02_Solicitud_Fisica.pdf'];
        } else {
            $mapa[] = ['tipo' => 'solicitud', 'view' => 'pdf.formatos.solicitud-pm', 'data' => $templateData, 'nombre' => 'F-PR-OCC-03_Solicitud_Moral.pdf'];
        }

        // Ficha Técnica
        $mapa[] = ['tipo' => 'ficha_tecnica', 'view' => 'pdf.formatos.ficha-tecnica', 'data' => $templateData, 'nombre' => 'F-PR-OCC-04_Ficha_Tecnica.pdf'];

        // Ingresos y Egresos — uno o dos según tipo de negocio
        if ($esEmprendimiento) {
            $mapa[] = [
                'tipo' => 'ingresos_egresos_proyeccion',
                'view' => 'pdf.formatos.ingresos-egresos',
                'data' => array_merge($templateData, [
                    'etiqueta_periodo' => 'Proyección: próximos 6 meses',
                    'ie_datos'         => $wizard['proyeccion_ingresos_egresos'] ?? [],
                ]),
                'nombre' => 'F-PR-OCC-05_Proyeccion_Ingresos_Egresos.pdf',
            ];
        } else {
            $mapa[] = [
                'tipo' => 'ingresos_egresos_historico',
                'view' => 'pdf.formatos.ingresos-egresos',
                'data' => array_merge($templateData, [
                    'etiqueta_periodo' => 'Historial: últimos 6 meses',
                    'ie_datos'         => $wizard['ingresos_egresos'] ?? [],
                ]),
                'nombre' => 'F-PR-OCC-05_Historial_Ingresos_Egresos.pdf',
            ];
            $mapa[] = [
                'tipo' => 'ingresos_egresos_proyeccion',
                'view' => 'pdf.formatos.ingresos-egresos',
                'data' => array_merge($templateData, [
                    'etiqueta_periodo' => 'Proyección: próximos 6 meses',
                    'ie_datos'         => $wizard['proyeccion_ingresos_egresos'] ?? [],
                ]),
                'nombre' => 'F-PR-OCC-05_Proyeccion_Ingresos_Egresos.pdf',
            ];
        }

        // Declaración de Bienes Aval
        if ($conAval) {
            $mapa[] = [
                'tipo'   => 'declaracion_aval',
                'view'   => $isArtesanal ? 'pdf.formatos.declaracion-aval-artesanal' : 'pdf.formatos.declaracion-aval',
                'data'   => $templateData,
                'nombre' => ($isArtesanal ? 'F-PR-OCC-06' : 'F-PR-OCC-07') . '_Declaracion_Bienes_Aval.pdf',
            ];
        }

        // Paquete de formalización (Anexos 8, 9, 12, 13, 14)
        $mapa[] = ['tipo' => 'pagare', 'view' => 'pdf.formatos.pagare', 'data' => $templateData, 'nombre' => 'F-PR-OCC-10_Pagare.pdf'];
        $mapa[] = ['tipo' => 'carta_compromiso', 'view' => 'pdf.formatos.carta-compromiso', 'data' => $templateData, 'nombre' => 'F-PR-OCC-11_Carta_Compromiso.pdf'];
        $mapa[] = ['tipo' => 'carta_no_servidor', 'view' => 'pdf.formatos.carta-no-servidor', 'data' => $templateData, 'nombre' => 'F-PR-OCC-15-16_Carta_No_Servidor_Publico.pdf'];
        $mapa[] = ['tipo' => 'cedula_geolocalizacion', 'view' => 'pdf.formatos.cedula-geolocalizacion', 'data' => $templateData, 'nombre' => 'F-PR-OCC-13_Cedula_Geolocalizacion.pdf'];
        $mapa[] = ['tipo' => 'instructivo_pago', 'view' => 'pdf.formatos.instructivo-pago', 'data' => $templateData, 'nombre' => 'GT-PR-OCC-04_Instructivo_Pago.pdf'];

        return $mapa;
    }

    private static function pdf($dompdf, string $view, array $data, string $nombre, int $solicitudId): array
    {
        $dompdf->loadView($view, $data);
        $rutaLocal = "solicitudes/{$solicitudId}/formatos/tmp_{$nombre}";
        Storage::disk('local')->put($rutaLocal, $dompdf->output());
        return [$nombre, $rutaLocal];
    }
}
