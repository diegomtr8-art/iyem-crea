<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use App\Models\SolicitudCredito;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialPagosController extends Controller
{
    public function excel(Credito $credito): StreamedResponse
    {
        // El crédito solo es del ciudadano si una de sus solicitudes está vinculada a él.
        $esSuyo = SolicitudCredito::where('user_id', auth()->id())
            ->where('credito_id', $credito->id)
            ->exists();
        abort_unless($esSuyo, 403, 'No tienes acceso a este crédito.');

        $credito->load('acreditado');

        $pagos = $credito->pagos()
            ->activos()
            ->orderBy('fecha_pago')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Historial de pagos');

        $sheet->setCellValue('A1', 'Acreditado:');
        $sheet->setCellValue('B1', $credito->acreditado?->nombre_completo ?? '');
        $sheet->setCellValue('A2', 'Contrato:');
        $sheet->setCellValue('B2', $credito->clave_contrato ?? '—');
        $sheet->setCellValue('A3', 'Generado:');
        $sheet->setCellValue('B3', now()->timezone('America/Merida')->format('d/m/Y H:i'));

        $headers = ['Folio', 'Fecha', 'Forma de pago', 'Referencia', 'Monto recibido',
                    'Aplicado a mora', 'Aplicado a interés', 'Aplicado a capital'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:H5')->applyFromArray($this->headerStyle());

        $row = 6;
        foreach ($pagos as $p) {
            $sheet->setCellValue('A' . $row, $p->folio);
            $sheet->setCellValue('B' . $row, $p->fecha_pago->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $p->forma_pago);
            $sheet->setCellValue('D' . $row, $p->referencia ?? '');
            $sheet->setCellValue('E' . $row, (float) $p->monto_recibido);
            $sheet->setCellValue('F' . $row, (float) $p->aplicado_mora);
            $sheet->setCellValue('G' . $row, (float) $p->aplicado_ordinario);
            $sheet->setCellValue('H' . $row, (float) $p->aplicado_capital);
            $row++;
        }

        $sheet->setCellValue('D' . $row, 'TOTAL');
        foreach (['E', 'F', 'G', 'H'] as $c) {
            $sheet->setCellValue($c . $row, $pagos->isEmpty() ? 0 : "=SUM({$c}6:{$c}" . ($row - 1) . ")");
            $sheet->getStyle("{$c}6:{$c}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $nombre = 'HistorialPagos_' . ($credito->clave_contrato ?? $credito->id) . '_' . date('Ymd') . '.xlsx';
        return $this->download($spreadsheet, $nombre);
    }

    private function headerStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }

    private function download(Spreadsheet $spreadsheet, string $nombre): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombre, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
