<?php

namespace App\Providers;

use App\Models\Credito;
use App\Models\ExpedienteJuridico;
use App\Models\Pago;
use App\Observers\CreditoObserver;
use App\Observers\ExpedienteJuridicoObserver;
use App\Observers\PagoObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Credito::observe(CreditoObserver::class);
        Pago::observe(PagoObserver::class);
        ExpedienteJuridico::observe(ExpedienteJuridicoObserver::class);
    }
}
