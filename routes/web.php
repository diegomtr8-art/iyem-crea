<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AcreditadoController;
use App\Http\Controllers\AnalisisCreditoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CondonacionFormalController;
use App\Http\Controllers\DesembolsoController;
use App\Http\Controllers\InteresadoController;
use App\Http\Controllers\Portal\EstadoCuentaController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperacionesController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ReestructuracionController;
use App\Http\Controllers\SimuladorController;
use App\Http\Controllers\SolicitudOperativoController;
use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\CobranzaJuridicaController;
use App\Http\Controllers\Portal\BeneficiarioController;
use App\Http\Controllers\Portal\SolicitudCreditoController;
use App\Http\Controllers\Portal\MiCreditoController;
use App\Http\Controllers\Portal\ExpedienteController;
use Illuminate\Support\Facades\Route;

// --- RUTAS PÚBLICAS (LANDING PAGE INFORMATIVA) ---
Route::get('/', [PublicController::class, 'index'])->name('welcome');
Route::get('/consultar-credito', fn() => redirect('/'))->name('public.consultar.redirect');
Route::post('/consultar-credito', [PublicController::class, 'consultar'])->name('public.consultar');

// --- PORTAL CIUDADANO (requiere auth + tipo ciudadano) ---
Route::middleware(['auth', 'ciudadano'])->prefix('mi-portal')->name('portal.')->group(function () {
    Route::get('/', [BeneficiarioController::class, 'index'])->name('dashboard');

    // Anuncios
    Route::post('anuncios/{anuncio}/leer', [BeneficiarioController::class, 'marcarLeido'])->name('anuncios.leer');
    Route::post('anuncios/leer-todos', [BeneficiarioController::class, 'marcarTodosLeidos'])->name('anuncios.leer-todos');

    // Expediente digital
    Route::get('expediente', [ExpedienteController::class, 'index'])->name('expediente');

    // Solicitud de crédito
    Route::get('solicitud', [SolicitudCreditoController::class, 'index'])->name('solicitud.index');
    Route::post('solicitud', [SolicitudCreditoController::class, 'store'])->name('solicitud.store');
    Route::put('solicitud', [SolicitudCreditoController::class, 'update'])->name('solicitud.update');
    Route::post('solicitud/documento', [SolicitudCreditoController::class, 'subirDocumento'])->name('solicitud.documento');
    Route::post('solicitud/enviar', [SolicitudCreditoController::class, 'enviar'])->name('solicitud.enviar');

    // Mi crédito activo
    Route::get('mi-credito', [MiCreditoController::class, 'index'])->name('credito');
    Route::get('mi-credito/estado-cuenta/pdf', [EstadoCuentaController::class, 'pdf'])->name('credito.estado-cuenta.pdf');
    Route::get('mi-credito/liquidacion-anticipada', [EstadoCuentaController::class, 'liquidacionAnticipada'])->name('credito.liquidacion');
});

// --- RUTAS OPERATIVAS (requiere auth + tipo operativo) ---
Route::middleware(['auth', 'verified', 'operativo'])->group(function () {

    // DASHBOARD
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PAGOS
    Route::get('creditos/{credito}/pagos/create', [PagoController::class, 'create'])->name('pagos.create');
    Route::post('creditos/{credito}/pagos', [PagoController::class, 'store'])->name('pagos.store');
    Route::post('pagos/{pago}/cancelar', [PagoController::class, 'cancelar'])->name('pagos.cancelar');
    Route::get('pagos/{pago}/recibo', [PagoController::class, 'recibo'])->name('pagos.recibo');
    Route::get('pagos/{pago}/recibo/pdf', [PagoController::class, 'recibosPdf'])->name('pagos.recibo.pdf');

    // ADMINISTRACIÓN
    Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);

    // INTERESADOS
    Route::post('interesados/{id}/convertir', [InteresadoController::class, 'convertir'])->name('interesados.convertir');
    Route::resource('interesados', InteresadoController::class);

    // ACREDITADOS
    Route::resource('acreditados', AcreditadoController::class);
    Route::get('acreditados/{acreditado}/expediente', [AcreditadoController::class, 'expediente'])->name('acreditados.expediente');
    Route::get('acreditados/{acreditado}/operaciones', [OperacionesController::class, 'index'])->name('operaciones.index');

    // CONDONACIÓN, DICTAMEN Y NUEVOS DOCUMENTOS PDF
    Route::post('creditos/{credito}/condonar', [CreditoController::class, 'condonar'])->name('creditos.condonar');
    Route::get('creditos/{credito}/dictamen', [CreditoController::class, 'dictamen'])->name('creditos.dictamen');
    Route::get('creditos/{credito}/dictamen/pdf', [CreditoController::class, 'dictamenPdf'])->name('creditos.dictamen.pdf');
    Route::get('creditos/{credito}/contrato/pdf', [CreditoController::class, 'contratoPdf'])->name('creditos.contrato.pdf');
    Route::get('creditos/{credito}/estado-cuenta/pdf', [CreditoController::class, 'estadoCuentaPdf'])->name('creditos.estado-cuenta.pdf');
    Route::get('creditos/{credito}/constancia/pdf', [CreditoController::class, 'constanciaPdf'])->name('creditos.constancia.pdf');

    // DESEMBOLSO
    Route::get('creditos/{credito}/desembolso', [DesembolsoController::class, 'create'])->name('creditos.desembolso.create');
    Route::post('creditos/{credito}/desembolso', [DesembolsoController::class, 'store'])->name('creditos.desembolso.store');

    // REESTRUCTURACIÓN
    Route::get('creditos/{credito}/reestructurar', [ReestructuracionController::class, 'create'])->name('creditos.reestructurar.create');
    Route::post('creditos/{credito}/reestructurar', [ReestructuracionController::class, 'store'])->name('creditos.reestructurar.store');

    // CONDONACIÓN FORMAL
    Route::get('creditos/{credito}/condonacion-formal', [CondonacionFormalController::class, 'create'])->name('creditos.condonacion-formal.create');
    Route::post('creditos/{credito}/condonacion-formal', [CondonacionFormalController::class, 'store'])->name('creditos.condonacion-formal.store');

    // REPORTES
    Route::get('reportes/cartera', [ReporteController::class, 'cartera'])->name('reportes.cartera');
    Route::get('reportes/pagos', [ReporteController::class, 'pagos'])->name('reportes.pagos');
    Route::get('reportes/adeudo/{credito}', [ReporteController::class, 'adeudo'])->name('reportes.adeudo');

    // SIMULADOR
    Route::get('simulador', [SimuladorController::class, 'index'])->name('simulador.index');

    // NOTIFICACIONES API
    Route::get('api/notificaciones/cuotas', [NotificacionController::class, 'cuotasManana'])->name('notificaciones.cuotas');

    // SOLICITUDES CIUDADANAS (módulo operativo)
    Route::get('solicitudes', [SolicitudOperativoController::class, 'index'])->name('solicitudes.index');
    Route::get('solicitudes/{solicitud}', [SolicitudOperativoController::class, 'show'])->name('solicitudes.show');
    Route::post('solicitudes/{solicitud}/cambiar-estatus', [SolicitudOperativoController::class, 'cambiarEstatus'])->name('solicitudes.cambiar-estatus');
    Route::post('solicitudes/{solicitud}/asignar', [SolicitudOperativoController::class, 'asignar'])->name('solicitudes.asignar');
    Route::post('solicitudes/documento/{documento}/revisar', [SolicitudOperativoController::class, 'revisarDocumento'])->name('solicitudes.revisar-documento');
    Route::post('solicitudes/{solicitud}/anuncio', [SolicitudOperativoController::class, 'enviarAnuncio'])->name('solicitudes.enviar-anuncio');
    Route::get('solicitudes/{solicitud}/registrar-credito', [SolicitudOperativoController::class, 'registrarCredito'])->name('solicitudes.registrar-credito');
    Route::post('solicitudes/{solicitud}/registrar-credito', [SolicitudOperativoController::class, 'guardarCredito'])->name('solicitudes.guardar-credito');

    // ANÁLISIS CREDITICIO
    Route::get('solicitudes/{solicitud}/analisis', [AnalisisCreditoController::class, 'create'])->name('solicitudes.analisis.create');
    Route::post('solicitudes/{solicitud}/analisis', [AnalisisCreditoController::class, 'store'])->name('solicitudes.analisis.store');

    // COBRANZA
    Route::get('cobranza', [CobranzaController::class, 'index'])->name('cobranza.index');
    Route::get('cobranza/{credito}', [CobranzaController::class, 'show'])->name('cobranza.show');
    Route::post('cobranza/{credito}/gestion', [CobranzaController::class, 'storeGestion'])->name('cobranza.gestion');
    Route::post('cobranza/{credito}/juridico', [CobranzaController::class, 'enviarJuridico'])->name('cobranza.juridico');

    // COBRANZA JURÍDICA
    Route::get('juridico', [CobranzaJuridicaController::class, 'index'])->name('juridico.index');
    Route::get('juridico/{juridico}', [CobranzaJuridicaController::class, 'show'])->name('juridico.show');
    Route::put('juridico/{juridico}', [CobranzaJuridicaController::class, 'update'])->name('juridico.update');

    // EXPORTACIONES EXCEL
    Route::get('exportar/movimientos/{acreditado}', [ExportController::class, 'movimientosAcreditado'])->name('operaciones.export');
    Route::get('exportar/cartera', [ExportController::class, 'cartera'])->name('exportar.cartera');
    Route::get('exportar/pagos', [ExportController::class, 'pagos'])->name('exportar.pagos');

    // AUDITORÍA
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

    // PRESUPUESTO
    Route::get('presupuesto', [PresupuestoController::class, 'index'])->name('presupuesto.index');
    Route::post('presupuesto', [PresupuestoController::class, 'store'])->name('presupuesto.store');

    // REPORTES ADICIONALES
    Route::get('reportes/beneficiarios', [ReporteController::class, 'beneficiarios'])->name('reportes.beneficiarios');
    Route::get('reportes/informe-cobranza', [ReporteController::class, 'informeCobranza'])->name('reportes.informe-cobranza');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
