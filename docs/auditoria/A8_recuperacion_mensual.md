# A.8 · Recuperación mensual

---

## Resumen

Consulta mensual y por modalidad, en [`A8_recuperacion_mensual.sql`](sql/A8_recuperacion_mensual.sql). Las consultas 1 y 2 son las que van a pantalla; de la 3 a la 8 son de verificación. Corrieron el 07/10/2026 sobre la base local, con la ventana noviembre 2024 a octubre 2026.

| Métrica (24 meses) | Base local (datos de prueba) | Producción |
|---|---|---|
| Total recibido | 238,963.52 | Sin verificar |
| Esperado | 479,472.34 | Sin verificar |
| % de recuperación | 49.84 | Sin verificar |
| Pagos / pagos cancelados | 90 / 0 | Sin verificar |
| Pagos descuadrados | 2 (688.02 sin aplicar) | Sin verificar |

Son datos de prueba de los seeders y de los scripts de escenarios del A.10 y el A.11. Los dos pagos descuadrados vienen del seeder de liquidaciones, no del código de la caja.

---

## Recibido y % de recuperación

| Número | Qué significa |
|---|---|
| Total recibido | El dinero que entró por caja en el mes, sin importar a qué cuota o a qué mes correspondía. |
| % de recuperación | El dinero que entró en el mes dividido entre lo que vencía ese mes según las tablas de amortización. |

No miden lo mismo. El recibido de agosto incluye atrasos de julio, mora y adelantos de septiembre; el esperado de agosto solo incluye las cuotas que vencían en agosto. Por eso el porcentaje puede pasar de 100 en un mes con liquidaciones anticipadas y quedar bajo en el mes siguiente, aunque nadie haya dejado de pagar.

---

## Reglas aplicadas

| Regla | Cómo quedó en la consulta |
|---|---|
| Pagos cancelados | `cancelado = 0`. Es el mismo filtro de [`DashboardController.php:98`](../../app/Http/Controllers/DashboardController.php#L98) y de los reportes de Excel. En la base local hay 0 cancelados |
| Mes del pago | `fecha_pago`, no `created_at` |
| Meses sin pagos | Se generan los 24 meses con un `WITH RECURSIVE` y se unen con `LEFT JOIN`; los que no tienen pagos salen en cero |
| Sin aplicar | Columna adicional: `monto_recibido` menos los tres aplicados. Es la diferencia entre el total recibido y la suma de las tres columnas de aplicado |
| Acreditados que pagaron | `COUNT(DISTINCT acreditado_id)` |
| Esperado | `SUM(cuota_fija)` de las cuotas con `fecha_vencimiento` en el mes, sin créditos `Cancelado` |
| Cuotas `Reestructurada` | Cuentan solo si vencían antes de la fecha de la reestructura que las sustituyó. Las posteriores las reemplaza el plan nuevo |
| Cuotas `Gracia` | No se filtran: tienen `cuota_fija = 0` ([`CreditService.php:37`](../../app/Services/CreditService.php#L37)) |
| % de recuperación | `NULL` cuando el esperado es cero, para no dividir entre cero |
| Ventana | `@hasta = CURDATE()`; del primer día del mes 23 meses atrás al último día del mes en curso |

Las cuotas `Reestructurada` sin filtro se cuentan dos veces: la cuota vieja y la del plan nuevo. En la ventana local son 25 cuotas `Reestructurada`: 20 vencían antes de la reestructura y cuentan (38,870.37); 5 vencían después y no cuentan (8,884.74, todas en octubre 2026, consulta 8).

---

## Resultado mensual

| Mes | Recibido | Capital | Ordinario | Mora | Sin aplicar | Pagos | Acreditados | Esperado | % |
|---|---|---|---|---|---|---|---|---|---|
| 2024-11 a 2025-07 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 0.00 | NULL |
| 2025-08 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 1,730.53 | 0.00 |
| 2025-09 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 1,730.53 | 0.00 |
| 2025-10 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 6,922.12 | 0.00 |
| 2025-11 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 8,652.65 | 0.00 |
| 2025-12 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 8,652.65 | 0.00 |
| 2026-01 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 8,652.65 | 0.00 |
| 2026-02 | 6,268.16 | 5,451.49 | 816.67 | 0.00 | 0.00 | 2 | 2 | 14,533.46 | 43.13 |
| 2026-03 | 8,730.65 | 7,624.95 | 1,105.70 | 0.00 | 0.00 | 3 | 3 | 22,804.41 | 38.28 |
| 2026-04 | 15,449.01 | 13,722.78 | 1,726.23 | 0.00 | 0.00 | 6 | 6 | 27,792.24 | 55.59 |
| 2026-05 | 21,067.13 | 19,100.14 | 1,966.99 | 0.00 | 0.00 | 9 | 9 | 35,464.33 | 59.40 |
| 2026-06 | 35,969.48 | 33,423.26 | 2,546.22 | 0.00 | 0.00 | 18 | 18 | 55,060.71 | 65.33 |
| 2026-07 | 43,871.69 | 40,294.95 | 2,709.03 | 867.71 | 0.00 | 23 | 23 | 68,426.63 | 64.11 |
| 2026-08 | 56,537.17 | 52,110.13 | 3,701.88 | 725.16 | 0.00 | 24 | 24 | 74,863.78 | 75.52 |
| 2026-09 | 51,070.23 | 49,393.74 | 336.11 | 652.36 | 688.02 | 5 | 5 | 79,131.04 | 64.54 |
| 2026-10 | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 | 0 | 0 | 65,054.61 | 0.00 |

La consulta devuelve una fila por mes; aquí los nueve meses en cero de 2024-11 a 2025-07 van juntos. Los esperados de agosto 2025 a enero 2026 son de los créditos de escenarios del A.10, que no tienen pagos registrados. Octubre 2026 es el mes en curso.

Septiembre tiene 5 pagos y 51,070.23: dos son liquidaciones anticipadas por 36,514.28. Esas liquidaciones dejan pagadas cuotas que vencen de octubre 2026 en adelante, y esas cuotas siguen en el esperado de su mes: octubre incluye 9,084.13 de cuotas que ya se pagaron en meses anteriores.

---

## Pagos descuadrados

La consulta de la trampa 2 (consulta 5) devuelve 2 pagos.

| Pago | Crédito | Fecha | Recibido | Suma de aplicados | Diferencia | Observaciones |
|---|---|---|---|---|---|---|
| 81 · REC-000081-2026 | 33 · CREA-2024-010 | 21/09/2026 | 20,766.42 | 20,349.76 | 416.66 | `Seeder \| Condonado: $416.66` |
| 90 · REC-000090-2026 | 35 · CREA-2024-032 | 21/09/2026 | 15,747.86 | 15,476.50 | 271.36 | `Seeder \| Condonado: $271.36` |

En los dos la diferencia es exactamente el interés futuro condonado por liquidación anticipada. [`Add30CreditosSeeder.php:258`](../../database/seeders/Add30CreditosSeeder.php#L258) toma como monto la suma de `pago_restante` con el interés futuro incluido, condona ese interés en la línea 284 y guarda el monto completo en la línea 326. Ninguno tiene `tipo_abono` ni `sobrante_aplicado`, así que no son sobrantes del ticket 4.3.

El código de la caja no reproduce este caso. [`PagoController.php:116`](../../app/Http/Controllers/PagoController.php#L116) topa la liquidación en capital pendiente más mora, y el sobrante de "Reducir cuota" o "Reducir plazo" se suma a `aplicado_capital` en la línea 278. Con esas dos reglas, un pago registrado por la caja debería cuadrar; en producción no lo verifiqué.

Mientras existan, el reporte muestra la diferencia en la columna `sin_aplicar` y el total recibido sigue siendo `monto_recibido`.

---

## Por modalidad

Consulta 2, los 24 meses juntos.

| Modalidad | Recibido | Capital | Ordinario | Mora | Sin aplicar | Pagos | Acreditados | Esperado | % recuperación | % del total |
|---|---|---|---|---|---|---|---|---|---|---|
| Artesanal | 33,987.50 | 33,987.50 | 0.00 | 0.00 | 0.00 | 23 | 8 | 38,812.50 | 87.57 | 14.22 |
| Sustentable | 18,774.48 | 17,922.44 | 852.04 | 0.00 | 0.00 | 9 | 5 | 68,001.34 | 27.61 | 7.86 |
| Emprendedores | 186,201.54 | 169,211.50 | 14,056.79 | 2,245.23 | 688.02 | 58 | 14 | 372,658.50 | 49.97 | 77.92 |
| Total | 238,963.52 | 221,121.44 | 14,908.83 | 2,245.23 | 688.02 | 90 | 27 | 479,472.34 | 49.84 | 100.00 |

La fila de total no la devuelve la consulta. Los acreditados no se suman entre meses ni entre modalidades: un acreditado que pagó en tres meses cuenta una vez en el total de la modalidad.

---

## Agosto 2026 verificado a mano

Listé los pagos y las cuotas de agosto con la consulta 4 y los sumé fuera de la base.

| Concepto | A mano | Consulta 1 |
|---|---|---|
| Recibido (24 pagos) | 56,537.17 | 56,537.17 |
| Capital | 52,110.13 | 52,110.13 |
| Ordinario | 3,701.88 | 3,701.88 |
| Mora | 725.16 | 725.16 |
| Acreditados distintos | 24 | 24 |
| Esperado (39 cuotas) | 74,863.78 | 74,863.78 |
| % | 75.52 | 75.52 |

Las 4 cuotas `Reestructurada` de agosto vencían antes de su reestructura y cuentan. Los 24 pagos de agosto se capturaron el 21/09/2026: por `created_at` el mes saldría en cero.

---

## Cuadre

| Comprobación | Resultado |
|---|---|
| Suma de los 24 meses (consulta 1) | 238,963.52 |
| Suma de las modalidades (consulta 2) | 238,963.52 |
| Total histórico de `pagos`, sin cancelados (consulta 3) | 238,963.52 |
| Pagos anteriores a la ventana / con fecha futura | 0.00 / 0.00 |
| Suma del esperado por mes y por modalidad | 479,472.34 / 479,472.34 |

En la base local el total de 24 meses es igual al histórico porque el primer pago es de febrero 2026. En producción la consulta 3 separa lo anterior a la ventana.

---

## Fecha de pago contra fecha de captura

| Agrupado por | Meses con pagos | Pagos en septiembre 2026 |
|---|---|---|
| `fecha_pago` | 8 (feb a sep 2026) | 5 |
| `created_at` | 1 | 90 |

85 de los 90 pagos tienen `created_at` en un mes distinto de `fecha_pago` (consulta 6). En la base local es porque los seeders se corrieron el 21/09/2026; en producción la diferencia será menor, pero existe cuando un pago se captura días después.

---

## Diferencia con el índice de recuperación del dashboard

[`DashboardController.php:182-198`](../../app/Http/Controllers/DashboardController.php#L182) calcula un índice de recuperación del mes en curso con otro denominador: excluye del esperado las cuotas `Pagado`, `Condonado` y `Reestructurada`. Cada cuota que se paga sale del esperado, así que el índice sube conforme avanza el mes.

| Mes | Esperado del reporte | Esperado con la regla del dashboard | % del reporte | % con la regla del dashboard |
|---|---|---|---|---|
| 2026-08 | 74,863.78 | 13,006.49 | 75.52 | 434.69 |
| 2026-09 | 79,131.04 | 59,431.64 | 64.54 | 85.93 |
| 2026-10 | 65,054.61 | 55,970.48 | 0.00 | 0.00 |

Los dos números van a llamarse igual en pantallas distintas. No modifiqué el dashboard.

---

## Tiempo de ejecución

Medí desde la línea de comandos, tres corridas por consulta, restando los 0.06 s de conexión. Para volumen copié las tablas a una base aparte, multiplicadas por 100 y por 1000, y la borré al terminar. MariaDB 10.4 de XAMPP, `innodb_buffer_pool_size` de 16 MB.

| Volumen | Mensual (s) | Por modalidad (s) |
|---|---|---|
| 90 pagos, 659 cuotas | 0.01 - 0.02 | 0.00 |
| 9,000 pagos, 65,900 cuotas | 0.15 - 0.21 | 0.15 - 0.16 |
| 90,000 pagos, 659,000 cuotas | 1.16 - 1.17 | 1.31 - 1.34 |

A 659,000 cuotas la parte de pagos tarda 0.04 s y la del esperado 0.9 s. Probé índices en `amortizaciones (fecha_vencimiento, credito_id, estado, cuota_fija, created_at)` y en `pagos (fecha_pago, cancelado, …)`: las dos consultas quedaron entre 0.05 y 0.4 s más lentas. La ventana de 24 meses cubre casi todas las filas y leer la tabla completa sale más barato que recorrer un índice. No hace falta índice nuevo.

---

## Para montarlo en pantalla

- `CURDATE()` usa la zona horaria del servidor de MariaDB. Igual que en el A.7, conviene pasar `@hasta` desde Laravel con `Carbon::now('America/Merida')->toDateString()`.
- `porcentaje_recuperacion` llega `NULL` en los meses sin esperado; en la gráfica va como hueco o como "sin cuotas", no como cero.
- El mes en curso está incompleto: su porcentaje siempre sale bajo hasta que termina.
- `sin_aplicar` va en la tabla, no en la gráfica. Si es distinto de cero, hay un pago que revisar.

---

## Lo que no pude determinar

- Si en producción hay pagos descuadrados; los 2 locales son del seeder.
- Cuántos pagos cancelados hay en producción y por cuánto; en la base local hay 0.
- Si en producción hay créditos con más de una reestructura. La regla toma la primera reestructura registrada después de crear la cuota; con los datos locales (una por crédito) no se pudo probar el caso.
- Si hay créditos `Cancelado` con cuotas o pagos en producción; en la base local no hay ninguno.
- El volumen real de `pagos` y `amortizaciones` en producción.

---

## Recomendación

Correr la consulta 5 en producción antes de publicar la pantalla: si sale algo distinto de cero, el ticket 4.3 tiene un caso que el código revisado no muestra. Qué índice de recuperación se queda, el de este reporte o el del dashboard, es decisión de la dirección; tener los dos con el mismo nombre va a dar dos cifras distintas para el mismo mes.
