<?php

namespace App\Http\Controllers;

use App\Models\Amortizacion;
use App\Models\Credito;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RespaldoExcelController extends Controller
{
    public function descargar(): StreamedResponse
    {
        set_time_limit(0);

        $spreadsheet = new Spreadsheet();

        // Hoja 1: Créditos
        $hoja = $spreadsheet->getActiveSheet()->setTitle('Creditos');
        $this->encabezado($hoja, [
            'ID', 'Clave contrato', 'No. contrato oficial', 'Folio solicitud',
            'Acreditado', 'RFC', 'CURP', 'Modalidad',
            'Monto otorgado', 'Plazo (meses)', 'Fecha entrega', 'Fecha contrato',
            'Tasa ordinaria', 'Tasa moratoria', 'Estatus',
        ]);

        $fila = 2;
        Credito::with(['acreditado:id,nombre_completo,rfc,curp', 'modalidad:id,nombre'])
            ->chunkById(200, function ($creditos) use ($hoja, &$fila) {
                $datos = $creditos->map(fn ($c) => [
                    $c->id,
                    $c->clave_contrato,
                    $c->numero_contrato_oficial,
                    $c->folio_solicitud,
                    $c->acreditado?->nombre_completo,
                    $c->acreditado?->rfc,
                    $c->acreditado?->curp,
                    $c->modalidad?->nombre,
                    (float) $c->monto_otorgado,
                    $c->plazo_meses,
                    $c->fecha_entrega?->format('Y-m-d'),
                    $c->fecha_contrato?->format('Y-m-d'),
                    (float) $c->tasa_interes_ordinario,
                    (float) $c->tasa_interes_moratorio,
                    $c->estatus,
                ])->all();

                $hoja->fromArray($datos, null, "A{$fila}");
                $fila += count($datos);
            });

        // Hoja 2: Amortizaciones
        $hoja2 = $spreadsheet->createSheet()->setTitle('Amortizaciones');
        $this->encabezado($hoja2, [
            'ID', 'Crédito ID', 'Clave contrato', 'Cuota', 'Vencimiento',
            'Saldo insoluto', 'Capital esperado', 'Interés ordinario esperado', 'Cuota fija',
            'Capital pagado', 'Interés ordinario pagado', 'Interés moratorio pagado',
            'Interés moratorio generado', 'Moratorio acumulado', 'Comisión pagada',
            'Pago restante', 'Estado', 'Último pago', 'Observaciones',
        ]);

        $fila2 = 2;
        Amortizacion::with('credito:id,clave_contrato')
            ->chunkById(1000, function ($cuotas) use ($hoja2, &$fila2) {
                $datos = $cuotas->map(fn ($a) => [
                    $a->id,
                    $a->credito_id,
                    $a->credito?->clave_contrato,
                    $a->numero_cuota,
                    $a->fecha_vencimiento?->format('Y-m-d'),
                    (float) $a->saldo_insoluto,
                    (float) $a->capital_esperado,
                    (float) $a->interes_ordinario_esperado,
                    (float) $a->cuota_fija,
                    (float) $a->capital_pagado,
                    (float) $a->interes_ordinario_pagado,
                    (float) $a->interes_moratorio_pagado,
                    (float) $a->interes_moratorio_generado,
                    (float) $a->moratorio_acumulado,
                    (float) $a->comision_pagada,
                    (float) $a->pago_restante,
                    $a->estado,
                    $a->fecha_ultimo_pago?->format('Y-m-d'),
                    $a->observaciones,
                ])->all();

                $hoja2->fromArray($datos, null, "A{$fila2}");
                $fila2 += count($datos);
            });

        $nombre = 'Respaldo_creditos_' . now('America/Merida')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nombre, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache',
        ]);
    }

    private function encabezado($hoja, array $columnas): void
    {
        $hoja->fromArray($columnas, null, 'A1');
        $ultima = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columnas));
        $hoja->getStyle("A1:{$ultima}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        ]);
        $hoja->freezePane('A2');
    }
}