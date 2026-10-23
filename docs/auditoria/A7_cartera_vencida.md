# A.7 · Cartera vencida por antigüedad

---

## Resumen

Consulta de detalle por crédito y resumen por cubeta de antigüedad, en [`A7_cartera_vencida.sql`](sql/A7_cartera_vencida.sql). Las consultas 1 y 2 son las que van a pantalla; de la 3 a la 6 son de verificación. Corrieron el 05/10/2026 sobre la base local.

| Cubeta (días) | Créditos | Saldo vencido | % del total |
|---|---|---|---|
| 1-30 | 26 | 48,155.73 | 39.82 |
| 31-60 | 2 | 7,941.80 | 6.57 |
| 61-90 | 4 | 20,460.57 | 16.92 |
| 91-180 | 3 | 27,945.68 | 23.11 |
| Más de 180 | 1 | 16,431.80 | 13.59 |
| Total | 36 | 120,935.58 | 100.00 |

Son datos de prueba: 30 créditos de los seeders y 6 (`AUD-A10-E1` a `E6`, ids 36 a 41) que dejó el script de escenarios del A.10. No hay cifras de producción.

---

## Reglas aplicadas

| Regla | Cómo quedó en la consulta |
|---|---|
| Cuota vencida | `fecha_vencimiento < CURDATE()` y `estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')`, la lista de [`UpdateMoratorio.php:19`](../../app/Console/Commands/UpdateMoratorio.php#L19) y de los controladores de pagos |
| Saldo vencido | `SUM(pago_restante)` de las cuotas vencidas. Es capital más interés ordinario; no incluye mora |
| Días de atraso | `DATEDIFF(CURDATE(), MIN(fecha_vencimiento))`: la cuota impaga más antigua |
| Cubetas | 1-30, 31-60, 61-90, 91-180, más de 180. Los límites son inclusivos: 30 días cae en 1-30 |
| Filtro adicional | `pago_restante > 0`. Una cuota sin nada pendiente no aporta saldo ni antigüedad. En la base local no excluye ninguna |
| Estatus del crédito | No se filtra. De los 36 créditos con cuotas vencidas, 26 tienen `estatus = 'Activo'` y 10 `Moroso`; ningún `Liquidado` tiene cuotas vencidas |

La consulta 1 devuelve `orden_cubeta` (1 a 5) además del texto de la cubeta, para ordenar en pantalla sin depender del texto. La 2 devuelve siempre las cinco cubetas, aunque alguna quede en cero.

---

## Días de gracia

El reporte cuenta desde el día 1 y no aplica los 5 días de gracia. La gracia de [`CreditService.php:88`](../../app/Services/CreditService.php#L88) decide desde cuándo se cobra mora, no desde cuándo se debe la cuota, y la cubeta pedida empieza en el día 1. Además el valor sale de `CREA_DIAS_GRACIA` ([`config/credito.php:5`](../../config/credito.php#L5)): si se escribiera un 5 fijo en el SQL, se desfasaría al cambiar la configuración.

Lo que cambia en la primera cubeta (consulta 5):

| Días de atraso | Créditos | Saldo vencido |
|---|---|---|
| 1 a 5 | 1 | 1,730.58 |
| 6 a 30 | 25 | 46,425.15 |

Si la dirección prefiere excluirlos, basta con cambiar `< CURDATE()` por `< CURDATE() - INTERVAL 5 DAY` en las dos consultas, y los días de atraso no cambian. También se puede mostrar a esos créditos marcados con `dias_atraso <= 5` sin sacarlos del total.

---

## Créditos reestructurados

[`ReestructuracionController.php:88-90`](../../app/Http/Controllers/ReestructuracionController.php#L88) marca las cuotas pendientes como `Reestructurada` y además las deja con `pago_restante = 0`. Contarlas no infla los pesos: infla la antigüedad y el número de cuotas.

En la base local hay 0 cuotas `Reestructurada`, así que lo probé dentro de una transacción con `ROLLBACK`: reestructuré el crédito 29 y corrí la consulta con y sin ese estado en la lista.

| Variante | Cuotas vencidas | Saldo | Días | Cubeta |
|---|---|---|---|---|
| Lista completa de cuatro estados | 0 | (no aparece) | | |
| Sin `Reestructurada` en la lista | 7 | 0.00 | 198 | Más de 180 |

Sin el filtro, el crédito sigue en la peor cubeta con saldo cero: sube el número de créditos de más de 180 días sin que suba el dinero.

---

## Cinco casos verificados a mano

Uno por cubeta. Los días se contaron con calendario desde la cuota más antigua hasta el 05/10/2026, y el saldo, multiplicando la cuota por el número de cuotas impagas (consulta 4 con cada id).

| Crédito | Cubeta | Cuota más antigua | Días a mano | Saldo a mano | Consulta |
|---|---|---|---|---|---|
| 29 · CREA-2024-022 | Más de 180 | 21/03/2026 | 10+30+31+30+31+31+30+5 = 198 | 7 × 2,347.40 = 16,431.80 | 198 · 16,431.80 |
| 31 · CREA-2024-030 | 91-180 | 21/06/2026 | 9+31+31+30+5 = 106 | 4 × 3,134.08 = 12,536.32 | 106 · 12,536.32 |
| 25 · CREA-2024-009 | 61-90 | 21/07/2026 | 10+31+30+5 = 76 | 3 × 2,640.82 = 7,922.46 | 76 · 7,922.46 |
| 32 · CREA-2024-035 | 31-60 | 21/08/2026 | 10+30+5 = 45 | 2 × 2,067.31 = 4,134.62 | 45 · 4,134.62 |
| 40 · AUD-A10-E5 | 1-30 | 05/09/2026 | 25+5 = 30 | 1,730.58 − 1,000.00 = 730.58 | 30 · 730.58 |

Los cinco coinciden. El 40 cubre dos bordes: una cuota `Parcial` (pagó 1,000 de 1,730.58) y el límite de 30 días, que cae en 1-30. El 29 comprueba la regla de la cuota más antigua: su cuota más reciente vencida tiene 14 días y el reporte le asigna 198.

Ningún crédito local tiene una cuota pagada posterior a una impaga, así que ese caso no está cubierto por los datos.

---

## Cuadre

| Comprobación | Resultado |
|---|---|
| Suma del resumen (consulta 2) | 120,935.58 |
| Suma del detalle (consulta 1) | 120,935.58 |
| Suma directa de cuotas, sin joins (consulta 3) | 120,935.58 |
| Créditos en detalle / en resumen / con cuotas vencidas | 36 / 36 / 36 |

Que el total con joins y sin joins coincida descarta créditos sin acreditado o sin modalidad que se perderían en el detalle. También cabe señalar que el numerador del índice de morosidad del dashboard ([`DashboardController.php:172-178`](../../app/Http/Controllers/DashboardController.php#L172)) da los mismos 120,935.58 en la base local.

---

## Tiempo de ejecución

En la base local (527 cuotas) las dos consultas tardan menos de 0.005 s. Para medir con volumen copié las tablas a una base aparte, multiplicadas por 100 y por 1000, y la borré al terminar. MariaDB 10.4 de XAMPP, `innodb_buffer_pool_size` de 16 MB.

| Volumen | Índices en `amortizaciones` | Detalle (s) | Resumen (s) |
|---|---|---|---|
| 527 cuotas | Los actuales | 0.001 - 0.003 | 0.001 |
| 52,700 cuotas | Los actuales | 0.03 - 0.06 | 0.03 - 0.05 |
| 527,000 cuotas | Los actuales | 5.1 - 5.3 | 2.0 - 2.1 |
| 527,000 cuotas | Ninguno además de la llave primaria | 1.0 - 1.1 | 0.5 |
| 527,000 cuotas | Los actuales + `(fecha_vencimiento, estado, credito_id, pago_restante)` | 0.87 - 0.91 | 0.24 - 0.34 |

La primera versión del detalle hacía el `GROUP BY` después de los joins y tardaba 4.5 - 5.0 s a 527,000 cuotas aun con el índice nuevo. La versión entregada agrupa primero las cuotas y después une créditos, acreditados y modalidad; da el mismo resultado en la base local.

El único índice de `amortizaciones` aparte de la llave primaria es `credito_id`. A 527,000 cuotas el optimizador recorre ese índice y luego lee cada fila, y sale cinco veces más lento que leer la tabla completa. El índice que falta es `(fecha_vencimiento, estado, credito_id, pago_restante)`: con él, las dos consultas se resuelven sin tocar la tabla. No lo creé.

---

## Para montarlo en pantalla

- `CURDATE()` usa la zona horaria del servidor de MariaDB, y el sistema calcula la mora con `America/Merida` ([`CreditService.php:79`](../../app/Services/CreditService.php#L79)). Conviene pasar la fecha desde Laravel como parámetro (`Carbon::now('America/Merida')->toDateString()`) en lugar de `CURDATE()`.
- Los alias no llevan acentos y la cubeta dice `Mas de 180`; el texto con acento va en la vista.
- La consulta 4 lleva `SET @credito`: es para verificar, no para pantalla.

---

## Lo que no pude determinar

- Cuántas cuotas y créditos hay en producción, y por lo tanto si el tiempo real queda cerca de la fila de 52,700 o de la de 527,000.
- La zona horaria del servidor de MariaDB en producción.
- Si en producción hay cuotas `Reestructurada`, `Condonado` o `Gracia` vencidas; en la base local hay 0 de cada una.
- Si en producción hay cuotas no pagadas con `pago_restante = 0`, que el filtro adicional sacaría del reporte.

---

## Recomendación

Crear el índice `(fecha_vencimiento, estado, credito_id, pago_restante)` antes de que la tabla pase de unas 100,000 cuotas. Si los días de gracia se excluyen o no es decisión de la dirección; el reporte queda contando desde el día 1.

Aparte del reporte: el dashboard filtra con tres estados, sin `Gracia` ([`DashboardController.php:50`](../../app/Http/Controllers/DashboardController.php#L50), y las líneas 145, 175, 184, 204 y 220). Las cuotas de gracia tienen `pago_restante = 0`, así que no mueven los pesos, pero sí el conteo de cuotas vencidas y el semáforo por créditos.
