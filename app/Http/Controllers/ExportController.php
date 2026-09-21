<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Pago;
use App\Models\Credito;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    // --- Estilos reutilizables ---
    private function headerStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }

    private function autoSize(object $sheet, array $columns): void
    {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // ---------------------------------------------------------------
    // MOVIMIENTOS DE UN ACREDITADO
    // ---------------------------------------------------------------
    public function movimientosAcreditado(Acreditado $acreditado): StreamedResponse
    {
        $acreditado->load('creditos');
        $credito = $acreditado->creditos->first();

        $pagos = Pago::with('cajero')
            ->where('acreditado_id', $acreditado->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimientos');

        // Encabezado de info
        $sheet->setCellValue('A1', 'Acreditado:');
        $sheet->setCellValue('B1', $acreditado->nombre_completo);
        $sheet->setCellValue('A2', 'Contrato:');
        $sheet->setCellValue('B2', $credito?->clave_contrato ?? '—');
        $sheet->setCellValue('A3', 'Generado:');
        $sheet->setCellValue('B3', now()->format('d/m/Y H:i'));

        // Encabezados tabla
        $headers = ['Folio', 'Fecha', 'Forma Pago', 'Referencia', 'Monto Recibido',
                    'Aplicado Mora', 'Aplicado Ordinario', 'Aplicado Capital', 'Registrado Por', 'Observaciones'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:J5')->applyFromArray($this->headerStyle());

        $row = 6;
        foreach ($pagos as $p) {
            $sheet->setCellValue('A' . $row, $p->folio);
            $sheet->setCellValue('B' . $row, $p->fecha_pago->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $p->forma_pago);
            $sheet->setCellValue('D' . $row, $p->referencia ?? '');
            $sheet->setCellValue('E' . $row, (float)$p->monto_recibido);
            $sheet->setCellValue('F' . $row, (float)$p->aplicado_mora);
            $sheet->setCellValue('G' . $row, (float)$p->aplicado_ordinario);
            $sheet->setCellValue('H' . $row, (float)$p->aplicado_capital);
            $sheet->setCellValue('I' . $row, $p->cajero?->name ?? 'Sistema');
            $sheet->setCellValue('J' . $row, $p->observaciones ?? '');
            $row++;
        }

        // Totales
        $sheet->setCellValue('D' . $row, 'TOTAL');
        $sheet->setCellValue('E' . $row, "=SUM(E6:E" . ($row - 1) . ")");
        $sheet->setCellValue('F' . $row, "=SUM(F6:F" . ($row - 1) . ")");
        $sheet->setCellValue('G' . $row, "=SUM(G6:G" . ($row - 1) . ")");
        $sheet->setCellValue('H' . $row, "=SUM(H6:H" . ($row - 1) . ")");
        $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);

        // Formato moneda
        $moneyFmt = '#,##0.00';
        foreach (['E', 'F', 'G', 'H'] as $c) {
            $sheet->getStyle("{$c}6:{$c}{$row}")
                  ->getNumberFormat()->setFormatCode($moneyFmt);
        }

        $this->autoSize($sheet, ['A','B','C','D','E','F','G','H','I','J']);

        $nombre = 'Movimientos_' . str_replace(' ', '_', $acreditado->nombre_completo) . '_' . date('Ymd') . '.xlsx';
        return $this->download($spreadsheet, $nombre);
    }

    // ---------------------------------------------------------------
    // REPORTE DE CARTERA COMPLETO
    // ---------------------------------------------------------------
    public function cartera(Request $request): StreamedResponse
    {
        $estatus = $request->get('estatus'); // Activo, Moroso, Liquidado, o null = todos

        $creditos = Credito::with(['acreditado', 'modalidad'])
            ->when($estatus, fn($q) => $q->where('estatus', $estatus))
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();

        // ---- Hoja 1: Resumen ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Cartera');

        $headers = [
            'No.', 'Clave Contrato', 'Nombre Acreditado', 'Municipio', 'Modalidad',
            'Monto Otorgado', 'Plazo', 'Tasa Ordinaria', 'Tasa Moratoria',
            'Fecha Entrega', 'Estatus',
            'Capital Recuperado', 'Capital Pendiente',
            'Interés Cobrado', 'Mora Cobrada',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet1->setCellValue($col . '1', $h);
            $col++;
        }
        $sheet1->getStyle('A1:O1')->applyFromArray($this->headerStyle());

        $row = 2;
        foreach ($creditos as $i => $c) {
            $capRec = Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_capital');
            $ordRec = Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_ordinario');
            $morRec = Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_mora');

            $sheet1->setCellValue('A' . $row, $i + 1);
            $sheet1->setCellValue('B' . $row, $c->clave_contrato);
            $sheet1->setCellValue('C' . $row, $c->acreditado?->nombre_completo ?? '');
            $sheet1->setCellValue('D' . $row, $c->acreditado?->municipio ?? '');
            $sheet1->setCellValue('E' . $row, $c->modalidad?->nombre ?? '');
            $sheet1->setCellValue('F' . $row, (float)$c->monto_otorgado);
            $sheet1->setCellValue('G' . $row, $c->plazo_meses . ' meses');
            $sheet1->setCellValue('H' . $row, (float)$c->tasa_interes_ordinario . '%');
            $sheet1->setCellValue('I' . $row, (float)$c->tasa_interes_moratorio . '%');
            $sheet1->setCellValue('J' . $row, $c->fecha_entrega ? Carbon::parse($c->fecha_entrega)->format('d/m/Y') : '');
            $sheet1->setCellValue('K' . $row, $c->estatus);
            $sheet1->setCellValue('L' . $row, (float)$capRec);
            $sheet1->setCellValue('M' . $row, round((float)$c->monto_otorgado - (float)$capRec, 2));
            $sheet1->setCellValue('N' . $row, (float)$ordRec);
            $sheet1->setCellValue('O' . $row, (float)$morRec);

            // Color por estatus
            $color = match ($c->estatus) {
                'Liquidado' => 'E8F5E9',
                'Moroso'    => 'FFF3E0',
                default     => 'FFFFFF',
            };
            $sheet1->getStyle("A{$row}:O{$row}")
                   ->getFill()->setFillType(Fill::FILL_SOLID)
                   ->getStartColor()->setRGB($color);

            $row++;
        }

        // Totales finales
        $sheet1->setCellValue('E' . $row, 'TOTALES');
        $sheet1->setCellValue('F' . $row, "=SUM(F2:F" . ($row - 1) . ")");
        $sheet1->setCellValue('L' . $row, "=SUM(L2:L" . ($row - 1) . ")");
        $sheet1->setCellValue('M' . $row, "=SUM(M2:M" . ($row - 1) . ")");
        $sheet1->setCellValue('N' . $row, "=SUM(N2:N" . ($row - 1) . ")");
        $sheet1->setCellValue('O' . $row, "=SUM(O2:O" . ($row - 1) . ")");
        $sheet1->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);

        foreach (['F', 'L', 'M', 'N', 'O'] as $c) {
            $sheet1->getStyle("{$c}2:{$c}{$row}")
                   ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $this->autoSize($sheet1, ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O']);

        $nombre = 'Cartera_IYEM_' . date('Ymd') . ($estatus ? "_{$estatus}" : '') . '.xlsx';
        return $this->download($spreadsheet, $nombre);
    }

    // ---------------------------------------------------------------
    // TODOS LOS PAGOS (rango de fechas)
    // ---------------------------------------------------------------
    public function pagos(Request $request): StreamedResponse
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $pagos = Pago::with(['acreditado', 'cajero', 'credito'])
            ->where('cancelado', false)
            ->whereBetween('fecha_pago', [$desde, $hasta])
            ->orderBy('fecha_pago')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pagos');

        $sheet->setCellValue('A1', 'Reporte de Pagos');
        $sheet->setCellValue('A2', "Del {$desde} al {$hasta}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Folio', 'Fecha', 'Acreditado', 'Contrato', 'Forma Pago',
                    'Referencia', 'Monto', 'Mora', 'Ordinario', 'Capital', 'Cajero'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $col++;
        }
        $sheet->getStyle('A4:K4')->applyFromArray($this->headerStyle());

        $row = 5;
        foreach ($pagos as $p) {
            $sheet->setCellValue('A' . $row, $p->folio);
            $sheet->setCellValue('B' . $row, $p->fecha_pago->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $p->acreditado?->nombre_completo ?? '');
            $sheet->setCellValue('D' . $row, $p->credito?->clave_contrato ?? '');
            $sheet->setCellValue('E' . $row, $p->forma_pago);
            $sheet->setCellValue('F' . $row, $p->referencia ?? '');
            $sheet->setCellValue('G' . $row, (float)$p->monto_recibido);
            $sheet->setCellValue('H' . $row, (float)$p->aplicado_mora);
            $sheet->setCellValue('I' . $row, (float)$p->aplicado_ordinario);
            $sheet->setCellValue('J' . $row, (float)$p->aplicado_capital);
            $sheet->setCellValue('K' . $row, $p->cajero?->name ?? 'Sistema');
            $row++;
        }

        $sheet->setCellValue('F' . $row, 'TOTAL');
        foreach (['G','H','I','J'] as $c) {
            $sheet->setCellValue($c . $row, "=SUM({$c}5:{$c}" . ($row - 1) . ")");
            $sheet->getStyle("{$c}5:{$c}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle("A{$row}:K{$row}")->getFont()->setBold(true);

        $this->autoSize($sheet, ['A','B','C','D','E','F','G','H','I','J','K']);

        $nombre = "Pagos_{$desde}_{$hasta}.xlsx";
        return $this->download($spreadsheet, $nombre);
    }

// REPORTE DE ANTIGÜEDAD DE SALDOS
public function antiguedad(Request $request): StreamedResponse
{
    $estatus   = $request->get('estatus');
    $modId     = $request->get('modalidad_id') ? (int)$request->get('modalidad_id') : null;
    $sexo      = $request->get('sexo');
    $municipio = $request->get('municipio');

    $creditosQuery = Credito::with(['acreditado', 'modalidad', 'amortizaciones'])
        ->when($estatus, fn($q) => $q->where('estatus', $estatus))
        ->when($modId, fn($q) => $q->where('modalidad_id', $modId))
        ->when($sexo, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('sexo', $sexo)))
        ->when($municipio, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('municipio', $municipio)));

    $creditos = $creditosQuery->get();

    $buckets = [
        'al_corriente' => ['rango' => 'Al corriente',    'creditos' => 0, 'capital' => 0, 'interes' => 0, 'mora' => 0, 'total' => 0],
        '1_30'         => ['rango' => '1 a 30 días',     'creditos' => 0, 'capital' => 0, 'interes' => 0, 'mora' => 0, 'total' => 0],
        '31_60'        => ['rango' => '31 a 60 días',    'creditos' => 0, 'capital' => 0, 'interes' => 0, 'mora' => 0, 'total' => 0],
        '61_90'        => ['rango' => '61 a 90 días',    'creditos' => 0, 'capital' => 0, 'interes' => 0, 'mora' => 0, 'total' => 0],
        'mas_90'       => ['rango' => 'Más de 90 días',  'creditos' => 0, 'capital' => 0, 'interes' => 0, 'mora' => 0, 'total' => 0],
    ];

    foreach ($creditos as $c) {
        $tasaDiaria = ($c->tasa_interes_moratorio / 100) / 360;
        $creditoContabilizado = [];

        foreach ($c->amortizaciones as $fila) {
            if (in_array($fila->estado, ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])) {
                continue;
            }

            $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();
            $capPend     = max(0, (float)$fila->capital_esperado - (float)$fila->capital_pagado);
            $intPend     = max(0, (float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado);

            $hoyMerida = Carbon::now('America/Merida')->startOfDay();

            if ($hoyMerida->gt($vencimiento)) {
                $diasAtraso = (int) $vencimiento->diffInDays($hoyMerida);

                if ($diasAtraso <= 30) {
                    $key = '1_30';
                } elseif ($diasAtraso <= 60) {
                    $key = '31_60';
                } elseif ($diasAtraso <= 90) {
                    $key = '61_90';
                } else {
                    $key = 'mas_90';
                }

                $moraActual = 0;
                if ($diasAtraso > 5) {
                    $sv = max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado);
                    $moraActual = round($sv * $tasaDiaria * $diasAtraso, 2);
                }

                $buckets[$key]['capital'] += $capPend;
                $buckets[$key]['interes'] += $intPend;
                $buckets[$key]['mora']    += $moraActual;
            } else {
                $key = 'al_corriente';
                $buckets['al_corriente']['capital'] += $capPend;
                $buckets['al_corriente']['interes'] += $intPend;
            }

            if (!isset($creditoContabilizado[$key])) {
                $buckets[$key]['creditos']++;
                $creditoContabilizado[$key] = true;
            }
        }
    }

    $granTotal = 0;
    foreach ($buckets as $k => $b) {
        $subtotal = $b['capital'] + $b['interes'] + $b['mora'];
        $buckets[$k]['total'] = round($subtotal, 2);
        $granTotal += $subtotal;
    }

    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Antigüedad de Saldos');

    // Título e info
    $sheet->setCellValue('A1', 'Reporte de Antigüedad de Saldos (Aging)');
    $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    // Headers
    $headers = ['Rango', 'Créditos', 'Capital Vencido', 'Interés Vencido', 'Mora', 'Total', '% de la Cartera'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '4', $h);
        $col++;
    }
    $sheet->getStyle('A4:G4')->applyFromArray($this->headerStyle());

    // Filas
    $row = 5;
    foreach ($buckets as $b) {
        $porcentaje = $granTotal > 0 ? round(($b['total'] / $granTotal), 4) : 0;

        $sheet->setCellValue('A' . $row, $b['rango']);
        $sheet->setCellValue('B' . $row, $b['creditos']);
        $sheet->setCellValue('C' . $row, round($b['capital'], 2));
        $sheet->setCellValue('D' . $row, round($b['interes'], 2));
        $sheet->setCellValue('E' . $row, round($b['mora'], 2));
        $sheet->setCellValue('F' . $row, "=SUM(C{$row}:E{$row})");
        $sheet->setCellValue('G' . $row, $porcentaje);

        $row++;
    }

    // Fila Total
    $sheet->setCellValue('A' . $row, 'Total');
    $sheet->setCellValue('B' . $row, "=SUM(B5:B" . ($row - 1) . ")");
    $sheet->setCellValue('C' . $row, "=SUM(C5:C" . ($row - 1) . ")");
    $sheet->setCellValue('D' . $row, "=SUM(D5:D" . ($row - 1) . ")");
    $sheet->setCellValue('E' . $row, "=SUM(E5:E" . ($row - 1) . ")");
    $sheet->setCellValue('F' . $row, "=SUM(F5:F" . ($row - 1) . ")");
    $sheet->setCellValue('G' . $row, "=SUM(G5:G" . ($row - 1) . ")");
    $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);

    // Formato de Moneda
    $moneyFmt = '#,##0.00';
    foreach (['C', 'D', 'E', 'F'] as $c) {
        $sheet->getStyle("{$c}5:{$c}{$row}")
              ->getNumberFormat()->setFormatCode($moneyFmt);
    }

    // Formato de Porcentaje
    $sheet->getStyle("G5:G{$row}")
          ->getNumberFormat()->setFormatCode('0.00%');

    $this->autoSize($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G']);

    $nombre = 'Reporte_Antiguedad_Saldos_' . date('Ymd') . '.xlsx';
    return $this->download($spreadsheet, $nombre);
}

    private function download(Spreadsheet $spreadsheet, string $nombre): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombre, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$nombre}\"",
            'Cache-Control'       => 'no-cache',
        ]);
    }
}
