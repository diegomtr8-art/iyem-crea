# Estados Actuales

- Activo
- Liquidado
- Vencido
- Moroso
- Cancelado

# Tabla de Transición de Estado

| Desde | Hacia | Cuándo pasa | Dónde está
|:---:|:---:|:---:|:---:|
| - | Activo | Al registrar un crédito nuevo | Acreditado Controller: 157, SolicitudOperativoController: 455 |
| Activo | Moroso | Al registrar o cancelar un pago, si queda alguna cuota vencida sin cubrir | PagoController: 208 y 371 |
| Activo o Moroso | Liquidado | Al registrar o cancelar un pago si ya no queda ninguna vencida | PagoController: 201 y 373 |
| Activo o Moroso | Liquidado | Al aplicar una condonación total | CondonaciónFormalController: 77, CreditoController: 74 |
| Cualquiera | Activo | Al reestructurar el crédito | ReestructuracionController: 125 |
| Cualquiera | Activo | Al marcar un expediente jurídico como Recuperada | CobranzaJuridicaController: 136 |

# Regla de recálculo

    $activasQuery = fn() => $credito->amortizaciones()
        ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia']);
        
        if ($activasQuery()->count() === 0) {
            $credito->update([EstadoCredito::LIQUIDADO]);
        } elseif ($activasQuery()->where('fecha_vencimiento', '<', now())->exists()) {
            $credito->update([EstadoCredito::MOROSO]);
        } else {
            $credito->update([EstadoCredito::ACTIVO]);
        }

# Huecos

## Hueco 1: Un crédito nunca se vuelve Moroso por sí solo

De acuerdo con la tabla de Transición de Estado, un crédito solamente cambia su estado a "Moroso" al registrar o cancelar un pago en PagoController.

Sin embargo, la definición de morosidad es lentitud o demora. En este caso se refiere a la demora de un pago. Esto se aplica en UpdateMoratorio, pero no modifica el estatus del credito a "Moroso", ocasionando que el credito siga con el estatus "Activo".

En el ambiente de pruebas se encuentran 21 creditos en esta situación. Contando los que aún están en el periodo de gracia de 5 días.


## Hueco 2: El estado Cancelado es inalcanzable

En CreditoController: 29, DashboardController: 203 y 219, DesembolsoController: 135, PagoController: 22, PresupuestoController: 24 y SolicitudOperativoController: 383 se filtra por el estado="Cancelado", pero nunca se asigna este estado.


## Hueco 3: No hay estado para Cobranza Jurídica

Solamente en dos ocasiones se cambia el estado de Crédito en CobranzaJuridicaController. La primera es cuando el estatus de expediente_juridico cambia a Recuperada, actualizando el estado del credito a "ACTIVO". Esto ocurre en la línea 139. La segunda sucede en la función reestructurarCredito en la línea 212, donde se pasa el estatus del crédito a "Activo". Cabe mencionar que esta línea no estaba marcada para hacer el cambio de literal a constante.

No hay otros lugares donde se cambie el estatus del crédito para poder distinguir los créditos en demanda de los créditos morosos, ni un estatus específico para estos.