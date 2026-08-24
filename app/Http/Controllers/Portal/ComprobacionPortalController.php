<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AnuncioCiudadano;
use App\Models\ComprobacionUso;
use App\Models\DocumentoComprobacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ComprobacionPortalController extends Controller
{
    public function enviar(Request $request, ComprobacionUso $comprobacion): RedirectResponse
    {
        $user = auth()->user();
        abort_if($comprobacion->acreditado->solicitud?->user_id !== $user->id, 403);
        abort_if(!in_array($comprobacion->estatus, ['Pendiente', 'Rechazada']), 422, 'Esta comprobación ya fue enviada.');

        $data = $request->validate([
            'observaciones_acreditado' => 'nullable|string|max:1000',
            'comprobantes'                    => 'required|array|min:1',
            'comprobantes.*.archivo'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'comprobantes.*.tipo'             => 'required|in:factura,nota_venta,foto_bien,otro',
            'comprobantes.*.descripcion'      => 'nullable|string|max:255',
            'comprobantes.*.monto'            => 'nullable|numeric|min:0',
            'comprobantes.*.proveedor'        => 'nullable|string|max:150',
        ]);

        foreach ($data['comprobantes'] as $comprobante) {
            $archivo   = $comprobante['archivo'];
            $extension = $archivo->getClientOriginalExtension();
            $ruta = $archivo->storeAs(
                "comprobaciones/{$comprobacion->id}",
                uniqid('doc_') . ".{$extension}",
                'local'
            );

            DocumentoComprobacion::create([
                'comprobacion_id' => $comprobacion->id,
                'tipo'            => $comprobante['tipo'],
                'descripcion'     => $comprobante['descripcion'] ?? null,
                'monto'           => $comprobante['monto'] ?? null,
                'proveedor'       => $comprobante['proveedor'] ?? null,
                'ruta_archivo'    => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
            ]);
        }

        $comprobacion->update([
            'estatus'                  => 'En_Revision',
            'observaciones_acreditado' => $data['observaciones_acreditado'] ?? null,
            'observaciones_operativo'  => null,
        ]);

        AnuncioCiudadano::create([
            'user_id' => $user->id,
            'titulo'  => 'Comprobación de uso enviada',
            'mensaje' => 'Recibimos tu comprobación de uso del crédito. La revisaremos en los próximos días.',
            'tipo'    => 'info',
            'url_accion' => route('portal.credito'),
        ]);

        return redirect()->route('portal.credito')->with('success', 'Comprobación enviada correctamente.');
    }

    public function descargarDocumento(DocumentoComprobacion $documento): Response
    {
        $user = auth()->user();
        $propietarioId = $documento->comprobacion->acreditado->solicitud?->user_id;

        abort_if($propietarioId !== $user->id && !$user->esOperativo(), 403);
        abort_if(!Storage::disk('local')->exists($documento->ruta_archivo), 404);

        return Storage::disk('local')->response($documento->ruta_archivo, $documento->nombre_original);
    }
}
