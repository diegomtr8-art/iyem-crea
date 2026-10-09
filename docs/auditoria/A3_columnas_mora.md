# A.3 · Las dos columnas de mora en `amortizaciones`

---

## Resumen

La tabla `amortizaciones` guarda la mora en dos columnas: `moratorio_acumulado` e `interes_moratorio_generado`. Ninguna pantalla lee las dos. Del código que escribe la mora, solo el cron escribe ambas.

Como en el A.2, las cifras salen de mi base local con los seeders de prueba.

| | Base local (datos de prueba) | Producción |
|---|---|---|
| Cuotas desincronizadas | 3 | No verificado |
| Créditos afectados | 2, de 35 | No verificado |
| Ciudadanos con una cifra equivocada en su portal | 2, de 35 | No verificado |
| Diferencia total | $2,245.23 | No verificado |
| Peor caso en una cuota | $867.71 | No verificado |
| Cuotas vencidas sin mora calculada | 23 | No verificado |
| Ciudadanos a los que no se les cobró mora | 9, de 35 | No verificado |
| Mora que esas cuotas deberían tener | $25,307.79 | No verificado |

Encontré el patrón que se buscaba: se desincronizan las cuotas cuyo pago se registró más de cinco días después del vencimiento, en créditos con tasa moratoria distinta de cero. Con esa regla se puede predecir cuáles están mal sin abrir las columnas de mora.

También cabe señalar que hay 23 cuotas vencidas con $0 de mora en las dos columnas. Están sincronizadas y están mal las dos. El cron nunca ha corrido aquí, y si tampoco corre en producción, entonces el problema no es que las columnas no coincidan: es que a nadie se le está cobrando la mora.

---

## Quién escribe y quién lee

Verifiqué los siete archivos, uno por uno. La mecánica que describe es correcta, pero la lista está incompleta y en dos puntos dice algo que no es.

| Quién | Archivo | Escribe |
|---|---|---|
| El cron de mora | [`UpdateMoratorio.php:39-40`](../../app/Console/Commands/UpdateMoratorio.php#L39) | Las dos |
| El registro de pagos | [`PagoController.php:244`](../../app/Http/Controllers/PagoController.php#L244) | Solo `interes_moratorio_generado` |
| La liquidación y condonación total | [`CreditoController.php:48`](../../app/Http/Controllers/CreditoController.php#L48) | Solo `interes_moratorio_generado`, lo pone en 0 |
| La reversa de un pago | [`PagoController.php:490-491`](../../app/Http/Controllers/PagoController.php#L490) | Las dos, restaurando un snapshot |
| El motor de crédito | [`CreditService.php:97`](../../app/Services/CreditService.php#L97) | Solo `interes_moratorio_generado` |

Se señala al motor de crédito como el culpable cuando no lo es: `CreditService::actualizarMoraDeCuota()` solo lo llaman los tests (`tests/Unit/MoraTest.php`). Ningún controlador, comando ni ruta lo usa. Eso sí, calcula la mora sobre `pago_restante` mientras que el cron y los pagos la calculan sobre `saldo_insoluto - capital_pagado`, así que el día que alguien lo conecte va a dar montos distintos de los demás.

El que sí desincroniza, y que no aparecía en la lista, es `PagoController`. Y no lo hace de vez en cuando: pasa en cada pago que entra tarde.

| Quién | Archivo | Lee | Lo ve |
|---|---|---|---|
| Portal del ciudadano | [`MiCreditoController.php:106`](../../app/Http/Controllers/Portal/MiCreditoController.php#L106) | `moratorio_acumulado` | El ciudadano |
| Estado de cuenta | [`EstadoCuentaController.php:80`](../../app/Http/Controllers/Portal/EstadoCuentaController.php#L80) | `moratorio_acumulado` | El ciudadano |
| PDF del estado de cuenta | [`estado-cuenta.blade.php:135`](../../resources/views/pdf/estado-cuenta.blade.php#L135) | `moratorio_acumulado` | El ciudadano |
| Reestructuración | [`ReestructuracionController.php:27`](../../app/Http/Controllers/ReestructuracionController.php#L27) | `moratorio_acumulado` | El operativo |
| Condonación formal | [`CondonacionFormalController.php:23`](../../app/Http/Controllers/CondonacionFormalController.php#L23) | `moratorio_acumulado` | El operativo |
| Consulta pública | [`PublicController.php:75`](../../app/Http/Controllers/PublicController.php#L75) | `interes_moratorio_generado` | El ciudadano |
| Condonación total | [`CreditoController.php:43`](../../app/Http/Controllers/CreditoController.php#L43) | `interes_moratorio_generado` | El operativo |

Los lectores son siete, no cinco, y no todos leen la misma columna. El ticket dice que todo lo que ve el ciudadano lee la columna que se quedó atrás, y no es así. La consulta pública lee la otra. O sea que el ciudadano no ve una cifra vieja: puede ver dos cifras distintas según por dónde entre. Si abre el portal ve una y si entra a la consulta pública ve otra, y ninguna de las dos pantallas le avisa que la otra existe.

---

## Qué tienen en común las cuotas desincronizadas

Probé las hipótesis del ticket contra los datos, una por una.

| Hipótesis | Resultado |
|---|---|
| Créditos que pasaron por reestructuración | No. En mi base hay 0 reestructuraciones y ninguna cuota en estado `Reestructurada`. |
| Cuotas que nunca tocó el cron | No separa nada. El cron no ha corrido, así que no tocó ninguna de las 455. |
| Cierto rango de fechas | No. Vencen en mayo, junio y julio, y en esos tres meses hay 63 cuotas vencidas. Solo 3 están mal. |
| Pago registrado con más de 5 días de atraso, con tasa moratoria mayor que cero | Sí. Las 3, y ninguna de más. |

La regla, en SQL:

```sql
DATEDIFF(fecha_ultimo_pago, fecha_vencimiento) > 5  Y  tasa_interes_moratorio > 0
```

La consulta 7 del archivo SQL la verifica. En mi base:

| Resultado | Cuotas |
|---|---|
| Predicho y desincronizado | 3 |
| Predicho sincronizado (tasa 0%) | 1 |
| Sin pago tardío y sincronizada | 451 |
| No predicho | 0 |

Funciona porque el desfase lo produce el registro de pagos. Cuando alguien cobra en caja, [`PagoController.php:244`](../../app/Http/Controllers/PagoController.php#L244) recalcula la mora en ese momento y la guarda solo en `interes_moratorio_generado`. Si el pago entró dentro de los cinco días de gracia, la mora es $0 y las dos columnas quedan iguales, así que no se nota nada.

Después la cuota pasa a `Pagado` y ahí se congela: el cron excluye ese estado ([`UpdateMoratorio.php:19`](../../app/Console/Commands/UpdateMoratorio.php#L19), junto con `Condonado`, `Reestructurada` y `Gracia`), así que nunca la vuelve a mirar.

Las tres afectadas:

| Crédito | Acreditado | Cuota | Venció | Se pagó | Atraso | Portal y PDF | Consulta pública |
|---|---|---|---|---|---|---|---|
| CREA-2024-002 | Juan Hernández Ávila | 1 | 21/05/2026 | 11/07/2026 | 51 días | $0.00 | $867.71 |
| CREA-2024-002 | Juan Hernández Ávila | 2 | 21/06/2026 | 05/08/2026 | 45 días | $0.00 | $725.16 |
| CREA-2024-014 | Jorge Poot Tamay | 1 | 21/07/2026 | 20/09/2026 | 61 días | $0.00 | $652.36 |

En las tres, `interes_moratorio_pagado` coincide con lo generado: la mora se cobró y el portal dice que no hubo.

---

## El problema que encontré de lado

Hay 23 cuotas vencidas, abiertas, en créditos con tasa moratoria mayor que cero, con $0 en las dos columnas. Son 9 acreditados y suman $25,307.79 de mora que debería estar calculada. La peor lleva 185 días vencida con $40,000 de saldo: debería tener unos $3,597 de mora y tiene cero.

La causa es que el cron nunca ha corrido en mi base. La tarea está programada en [`routes/console.php:12`](../../routes/console.php#L12), diario a las 8:00 hora de Mérida, pero Laravel necesita que el servidor ejecute `php artisan schedule:run` cada minuto. Las consultas 1 a 7 no lo detectan, porque ahí las dos columnas coinciden. Lo detecta la 8.

---

## Lo que no pude determinar

- Cuántas cuotas y cuánto dinero hay en producción. Sin acceso a esa base no hay forma de medirlo.
- Si el cron corre en el servidor. Desde el repositorio no se ve.
- Si alguna condonación total dejó mora visible en el portal. En mi base no hay ninguna condonación registrada.
- Desde cuándo existe el desfase. `amortizaciones` no tiene bitácora.

---

## Recomendación: cuál columna debería sobrevivir

Para fines de practicidad, `interes_moratorio_generado`: es la más completa y la más fácil de migrar. A largo plazo yo veo mejor opción mantener esta columna. La decisión la toma Diego.

| Criterio | `moratorio_acumulado` | `interes_moratorio_generado` |
|---|---|---|
| Lectores | 5 | 2 |
| Escritores | 2: el cron y la reversa de pagos | 6: el cron, los pagos, la condonación, la creación de créditos, la reestructuración, la cobranza jurídica |
| Completitud | Solo está al día si el cron corrió hoy. En las cuotas pagadas tarde se queda en $0. | Tiene el último valor calculado, lo escriba quien lo escriba. |
| Costo de migrar si sobrevive | Cambiar los escritores que hoy no la tocan y rellenar datos con un `UPDATE` | Cambiar 5 lecturas, una línea cada una, y quitar una línea del cron. Sin mover datos. |

Que una columna esté más completa no la vuelve la correcta. En las 23 cuotas vencidas sin mora, las dos dicen $0. Unificar columnas no arregla eso: lo arregla poner el cron a correr.

Las consultas están en [`sql/A3_mora.sql`](sql/A3_mora.sql), probadas en MariaDB 10.4.
