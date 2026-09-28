# A.4 · El estatus de los créditos no se recalcula solo

---

## Resumen

La columna `creditos.estatus` solo cambia cuando alguien hace algo sobre el crédito: registrar o cancelar un pago, liquidar, condonar, reestructurar. Nada la recalcula con el paso del tiempo. Un crédito que deja de pagar se queda en `Activo`.

Los créditos se sembraron el 21/09/2026 y las consultas corrieron el 28/09/2026: la base tiene una semana de antigüedad.

| | Base local (datos de prueba) |
|---|---|
| Créditos | 35 |
| Con estatus que no coincide con la regla | 20 |
| `Activo` que deberían ser `Moroso` | 20, de 21 en `Activo` |
| Otras combinaciones mal clasificadas | 0 |
| Saldo insoluto de esos 20 | $374,396.94 |
| Monto vencido de esos 20 | $38,772.35 |
| Créditos en `Cancelado` | 0 |

De los 21 créditos que el sistema muestra como `Activo`, solo uno lo es. Los otros 20 tienen una cuota vencida sin pagar.

---

## Quién escribe el estatus

Verifiqué todas las asignaciones de `estatus` sobre `creditos`. `PagoController` no es el único que lo escribe, pero ninguno de los demás lo recalcula de forma periódica, así que la conclusión no cambia.

| Quién | Archivo | Pone |
|---|---|---|
| Registro de pago | [`PagoController.php:307-317`](../../app/Http/Controllers/PagoController.php#L307) | La regla completa |
| Cancelación de pago | [`PagoController.php:543-553`](../../app/Http/Controllers/PagoController.php#L543) | La regla completa |
| Alta de crédito desde acreditado | [`AcreditadoController.php:158`](../../app/Http/Controllers/AcreditadoController.php#L158) | `Activo` |
| Alta de crédito desde solicitud | [`SolicitudOperativoController.php:456`](../../app/Http/Controllers/SolicitudOperativoController.php#L456) | `Activo` |
| Liquidación y condonación total | [`CreditoController.php:75`](../../app/Http/Controllers/CreditoController.php#L75) | `Liquidado` |
| Condonación formal | [`CondonacionFormalController.php:79`](../../app/Http/Controllers/CondonacionFormalController.php#L79) | `Liquidado` |
| Reestructuración | [`ReestructuracionController.php:126`](../../app/Http/Controllers/ReestructuracionController.php#L126) | `Activo` |
| Cobranza jurídica, expediente `Recuperada` | [`CobranzaJuridicaController.php:139`](../../app/Http/Controllers/CobranzaJuridicaController.php#L139) | `Activo`, sin revisar cuotas |
| Cobranza jurídica, expediente `Reestructurada` | [`CobranzaJuridicaController.php:212`](../../app/Http/Controllers/CobranzaJuridicaController.php#L212) | `Activo` |
| Cron de mora | [`UpdateMoratorio.php`](../../app/Console/Commands/UpdateMoratorio.php) | Nada |

También cabe señalar el caso de `Recuperada`: pone `Activo` aunque el crédito siga con cuotas vencidas. Ahí el estatus queda mal desde que se guarda, no por el paso del tiempo.

---

## Guardado contra calculado

Apliqué la regla de `PagoController` a los 35 créditos (consulta 3).

| Guardado | Calculado | Créditos |
|---|---|---|
| `Activo` | `Moroso` | 20 |
| `Moroso` | `Moroso` | 10 |
| `Liquidado` | `Liquidado` | 4 |
| `Activo` | `Activo` | 1 |

Todos los errores van en la misma dirección. No hay `Moroso` que deberían ser `Activo` ni `Liquidado` mal puestos: esos solo se producen por un pago, y el pago sí recalcula.

Ningún crédito de la base carece de amortizaciones. Si existiera uno, la consulta lo marcaría `Liquidado`, porque no tendría cuotas activas.

---

## El dinero, por antigüedad

Días contados desde la cuota activa vencida más antigua de cada crédito (consulta 5). El saldo insoluto es `monto_otorgado` menos todo el capital pagado.

| Antigüedad | Créditos | Saldo insoluto | Monto vencido |
|---|---|---|---|
| 1-30 días | 20 | $374,396.94 | $38,772.35 |
| 31-60 días | 0 | $0.00 | $0.00 |
| 61-90 días | 0 | $0.00 | $0.00 |
| Más de 90 días | 0 | $0.00 | $0.00 |

Todos caen en el primer tramo porque la base tiene una semana. Los seeders dejaron el estatus correcto el 21/09 y las cuotas de 20 créditos vencieron el 21 y el 23.

19 de los 20 llevan exactamente 5 días vencidos. La regla del estatus no tiene días de gracia: una cuota vencida ayer ya hace `Moroso` al crédito. La mora, en cambio, empieza el día 6 ([`UpdateMoratorio.php:32`](../../app/Console/Commands/UpdateMoratorio.php#L32)). Esos 19 son morosos para la regla del estatus y todavía no generan mora.

---

## Qué pantallas se equivocan

No todo lo que lee el estatus da una cifra mala: el índice de morosidad del dashboard suma las cuotas vencidas de créditos `Activo` y `Moroso` juntos, así que no depende de que el estatus esté al día.

| Qué | Archivo | En mi base muestra | Debería mostrar |
|---|---|---|---|
| Conteo de activos y morosos, dashboard | [`DashboardController.php:37-38`](../../app/Http/Controllers/DashboardController.php#L37) | 21 y 10 | 1 y 30 |
| Cartera vencida, dashboard | [`DashboardController.php:113-118`](../../app/Http/Controllers/DashboardController.php#L113) | $72,779.80 | $111,552.15 |
| Índice de morosidad, dashboard | [`DashboardController.php:171-180`](../../app/Http/Controllers/DashboardController.php#L171) | 12.85% | 12.85% |
| Resumen del reporte de cartera | [`ReporteController.php:87-89`](../../app/Http/Controllers/ReporteController.php#L87) | 21 y 10 | 1 y 30 |
| Tarjetas de acreditados | [`AcreditadoController.php:43-44`](../../app/Http/Controllers/AcreditadoController.php#L43) | 21 y 10 | 1 y 30 |
| Lista de cobranza | [`CobranzaController.php:18`](../../app/Http/Controllers/CobranzaController.php#L18) | 10 créditos | 30 créditos |
| KPIs de cobranza | [`CobranzaController.php:188`](../../app/Http/Controllers/CobranzaController.php#L188) | 10 créditos | 30 créditos |

La cartera vencida del dashboard deja fuera los $38,772.35 vencidos de los 20 créditos. La lista de cobranza tampoco los muestra: esos 20 acreditados no le aparecen a nadie para llamarles.

Las cifras de "debería mostrar" son las de la consulta 6 y los conteos de la consulta 3. El 12.85% sale de $111,552.15 entre $868,000 de cartera bruta.

---

## Casos

### CREA-2024-003 · Rosa Canche Tzec · el más vencido que sigue en `Activo`

| Dato | Valor |
|---|---|
| Monto otorgado | $15,000.00 |
| Pagos registrados | 0 |
| Saldo insoluto | $15,000.00 |
| Cuota 1 | Vence 21/09/2026, $2,536.58, `Pendiente` |
| Días vencido al 28/09 | 7 |
| Estatus guardado | `Activo` |
| Última modificación del crédito | 21/09/2026 18:10, la misma que su creación |

No ha pagado un peso. La primera cuota venció el mismo día en que se guardó el crédito, así que en ese momento todavía no estaba vencida y el estatus quedó en `Activo`. Desde entonces nadie ha tocado el registro.

### CREA-2024-018 · Noé Canul Herrera · el de mayor saldo

| Dato | Valor |
|---|---|
| Monto otorgado | $80,000.00 |
| Pagos registrados | 7, el último el 21/08/2026 |
| Cuotas 1 a 7 | `Pagado`, todas dos días antes del vencimiento |
| Cuota 8 | Vence 23/09/2026, $3,581.81, `Pendiente` |
| Saldo insoluto | $57,808.66 |
| Estatus guardado | `Activo` |

Siete meses pagando antes de tiempo y un mes sin pagar. La regla lo haría `Moroso` desde el 24/09. Es el crédito que más pesa en los $374,396.94.

### CREA-2024-005 · el único `Activo` bien clasificado

| Dato | Valor |
|---|---|
| Monto otorgado | $50,000.00 |
| Pagos registrados | 5, el último el 21/08/2026 |
| Primera cuota abierta | Cuota 10, vence 21/01/2027, `Parcial` |
| Saldo insoluto | $31,204.41 |
| Estatus guardado | `Activo` |

Tiene pagado por adelantado hasta diciembre. Si deja de pagar, seguirá en `Activo` también después del 21/01/2027, igual que los otros 20.

---

## El estado `Cancelado`

| | Base local |
|---|---|
| Créditos en `Cancelado` | 0 |
| Asignaciones de `Cancelado` a `creditos.estatus` en el código | 0 |
| Líneas del backend que lo filtran | 8, en 6 archivos |

Confirmado: ningún crédito está en `Cancelado` y ninguna línea del código lo asigna. La constante `EstadoCredito::CANCELADO` existe en [`EstadoCredito.php:12`](../../app/Enums/EstadoCredito.php#L12), pero nadie la usa. Los filtros son más de 5:

| Archivo | Líneas | Qué hace |
|---|---|---|
| [`PagoController.php`](../../app/Http/Controllers/PagoController.php#L24) | 24, 79 | Bloquea registrar pagos |
| [`CreditoController.php`](../../app/Http/Controllers/CreditoController.php#L29) | 29 | Bloquea liquidar |
| [`DashboardController.php`](../../app/Http/Controllers/DashboardController.php#L203) | 203, 219 | Excluye de la alerta de candidatos a jurídico y del semáforo de cartera |
| [`DesembolsoController.php`](../../app/Http/Controllers/DesembolsoController.php#L135) | 135 | Excluye del presupuesto ejercido |
| [`PresupuestoController.php`](../../app/Http/Controllers/PresupuestoController.php#L24) | 24 | Excluye del presupuesto ejercido |
| [`SolicitudOperativoController.php`](../../app/Http/Controllers/SolicitudOperativoController.php#L383) | 383 | Excluye del presupuesto ejercido |

En el frontend, [`Acreditados/Show.vue`](../../resources/js/Pages/Acreditados/Show.vue#L168) lo usa para ocultar acciones (líneas 168 y 234), y tres vistas le tienen color asignado. Los `Cancelado` de [`PagoObserver.php:31`](../../app/Observers/PagoObserver.php#L31) y de `Operaciones/Index.vue` son de pagos, no de créditos.

Los tres filtros de presupuesto indican que la intención era que un crédito cancelado devolviera su monto al presupuesto. Hoy no hay forma de cancelar un crédito desde el sistema.

---

## Lo que no pude determinar

- Si la regla sin días de gracia es la que se quiere para el estatus, o si debería respetar los 5 días de la mora.

---

## Recomendación

Pasar los 20 créditos a `Moroso` triplica el número de morosos del dashboard de un día para otro. Mi opinión es que el recálculo se agregue al cron para que no vuelva a pasar. Sobre `Cancelado`, la decisión es si se construye la baja de créditos o se quita el estado.

Las consultas están en [`sql/A4_estatus.sql`](sql/A4_estatus.sql), probadas en MariaDB 10.4.
