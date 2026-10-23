# A.11 · Verificación independiente de la reestructuración

---

## Resumen

Reestructuré 6 créditos de prueba desde la pantalla, con la rama `docs/crea-reestructuracion-anatocismo` (commit `54a611a`), y los recalculé con un script en Python que no usa el código del sistema. La base y la tabla nueva coinciden al centavo. La primera cuota nueva vence un mes después de la fecha de inicio de pagos capturada, y `mora_condonada` no es la mora a la fecha de reestructura sino la que dejó el último cron.

Las líneas citadas de `ReestructuracionController.php` y `Reestructuracion.vue` son las del commit `54a611a`, no las de `develop`.

| Métrica | Base local (datos de prueba) | Producción |
|---|---|---|
| Créditos reestructurados y comparados | 6 | No verificado |
| Base (`saldo_al_momento`) que coincide | 6 de 6 | No verificado |
| Caso real CREA-2026-101 reproducido | $27,177.42 en sistema y en script | No verificado |
| Cuotas nuevas comparadas | 108 | No verificado |
| Diferencias en `cuota_fija`, `capital_esperado`, `interes_ordinario_esperado`, `saldo_insoluto` | 0 | No verificado |
| Cuotas nuevas con fecha distinta a la esperada | 108 de 108 (un mes después) | No verificado |
| `mora_condonada` igual a la mora a la fecha de reestructura (regla del cron) | 5 de 6 | No verificado |

---

## Cómo calculé

[`recalculo_reestructuracion.py`](../../tools/auditoria/recalculo_reestructuracion.py) lee cuatro exportaciones de la base local ([`A11_datos/`](A11_datos/), consultas en [`sql/A11_reestructuracion.sql`](sql/A11_reestructuracion.sql)): la tabla de cada crédito antes de reestructurar, la tabla después, el registro de `reestructuraciones` y los créditos. De `reestructuraciones` solo toma lo que capturó el operador (fecha, plazo, tasa, inicio de pagos); base, mora y tabla las recalcula.

| Concepto | Cómo lo calcula el script |
|---|---|
| Capital pendiente | `monto_otorgado − Σ capital_pagado` de todas las cuotas. Lo cruza con `Σ (capital_esperado − capital_pagado)` de las cuotas abiertas; coinciden en los 6 |
| Interés devengado | Cuotas abiertas con vencimiento ≤ fecha de reestructura. El interés esperado sale de rehacer la tabla del contrato, no del guardado; resta `interes_ordinario_pagado` |
| Base | Capital pendiente + interés devengado, redondeada a centavos |
| Tabla nueva | Francesa, `i = tasa / 1200` (multiplica antes de dividir), `Decimal` de 40 dígitos, `ROUND_HALF_UP`. Última cuota: capital = saldo restante. Con tasa 0, base / plazo |
| Fechas | La cuota 1 vence en la fecha de inicio de pagos, la k en inicio + (k − 1) meses, con el desborde de fin de mes de Carbon |
| Numeración | Sigue después de la última cuota existente |
| Mora | Por cuota abierta con más de 5 días de atraso a la fecha de reestructura, tasa moratoria / 360, menos lo pagado. Con dos bases: la del cron (`saldo_insoluto − capital_pagado`) y la del Acuerdo (capital vencido de la cuota, ver A.10) |

El crédito del caso real no existe en mi base. Lo creé con [`a11_caso_real.php`](../../tools/auditoria/a11_caso_real.php) usando el motor real: AUD-A11-REAL (id 43), $30,000 a 24 meses, 7%, entrega 2026-01-15, cuotas 1 a 3 pagadas. El mismo script crea el usuario operativo de prueba con el que capturé. Antes de reestructurar corrí `php artisan crea:update-moratorio` el 2026-10-06.

No corrí los seeders: no son idempotentes (`Acreditado::create`, `ModalidadCrea::create`) y la base ya tenía sus datos.

---

## Los 6 créditos

| Crédito | Modalidad | Caso | Fecha reestructura | Plazo / tasa nueva | Base sistema | Base script | Diferencia |
|---|---|---|---|---|---|---|---|
| 43 AUD-A11-REAL | Emprendedores | Caso real, mora | 2026-09-25 | 12 / 7% | 27,177.42 | 27,177.42 | 0.00 |
| 2 CREA-2024-002 | Emprendedores | Mora acumulada | 2026-10-06 | 18 / 7% | 31,804.37 | 31,804.37 | 0.00 |
| 5 CREA-2024-005 | Emprendedores | Cuota Parcial | 2026-10-06 | 24 / 7% | 31,204.41 | 31,204.41 | 0.00 |
| 22 CREA-2024-021 | Artesanal | Tasa 0 | 2026-10-06 | 6 / 0% | 5,000.00 | 5,000.00 | 0.00 |
| 29 CREA-2024-022 | Emprendedores | 7 cuotas vencidas, tasa nueva distinta | 2026-10-06 | 36 / 6.5% | 41,371.82 | 41,371.82 | 0.00 |
| 30 CREA-2024-027 | Sustentable | 4 cuotas vencidas | 2026-10-06 | 12 / 5% | 25,365.62 | 25,365.62 | 0.00 |

| Campo (108 cuotas nuevas) | Exactas | Redondeo (≤ 0.01) | Sospechosa (0.02 a 1.00) | Error (> 1.00) |
|---|---|---|---|---|
| `cuota_fija` | 108 | 0 | 0 | 0 |
| `capital_esperado` | 108 | 0 | 0 | 0 |
| `interes_ordinario_esperado` | 108 | 0 | 0 | 0 |
| `saldo_insoluto` | 108 | 0 | 0 | 0 |

El detalle de cada diferencia está en [`A11_diferencias.csv`](A11_diferencias.csv).

---

## Crédito 43 desarrollado a mano

Base al 2026-09-25: cuotas 1 a 3 pagadas, 4 a 8 vencidas, 9 a 24 futuras.

| Concepto | Monto |
|---|---|
| Capital pendiente: 30,000.00 − (1,168.18 + 1,174.99 + 1,181.85) | 26,474.98 |
| Interés devengado, cuotas 4 a 8: 154.44 + 147.50 + 140.53 + 133.51 + 126.46 | 702.44 |
| Base | 27,177.42 |
| Base anterior (Σ `pago_restante`, incluía interés futuro) | 28,206.73 |
| Diferencia | 1,029.31 |

`i = 0.07 / 12 = 0.0058333…`. Cuota teórica: `27,177.42 × i / (1 − (1 + i)^−12) = 2,351.573720`.

| Cuota | Vence (inicio + k − 1) | Vence en sistema | Saldo inicial | Interés | Capital | Cuota | Sistema: cuota / capital / interés / saldo |
|---|---|---|---|---|---|---|---|
| 25 | 2026-10-25 | 2026-11-25 | 27,177.42 | 158.53 | 2,193.04 | 2,351.57 | 2,351.57 / 2,193.04 / 158.53 / 27,177.42 |
| 26 | 2026-11-25 | 2026-12-25 | 24,984.38 | 145.74 | 2,205.83 | 2,351.57 | 2,351.57 / 2,205.83 / 145.74 / 24,984.38 |
| 27 | 2026-12-25 | 2027-01-25 | 22,778.55 | 132.87 | 2,218.70 | 2,351.57 | 2,351.57 / 2,218.70 / 132.87 / 22,778.55 |
| 28 | 2027-01-25 | 2027-02-25 | 20,559.85 | 119.93 | 2,231.64 | 2,351.57 | 2,351.57 / 2,231.64 / 119.93 / 20,559.85 |
| 29 | 2027-02-25 | 2027-03-25 | 18,328.21 | 106.91 | 2,244.66 | 2,351.57 | 2,351.57 / 2,244.66 / 106.91 / 18,328.21 |
| 30 | 2027-03-25 | 2027-04-25 | 16,083.55 | 93.82 | 2,257.75 | 2,351.57 | 2,351.57 / 2,257.75 / 93.82 / 16,083.55 |
| 31 | 2027-04-25 | 2027-05-25 | 13,825.80 | 80.65 | 2,270.92 | 2,351.57 | 2,351.57 / 2,270.92 / 80.65 / 13,825.80 |
| 32 | 2027-05-25 | 2027-06-25 | 11,554.88 | 67.40 | 2,284.17 | 2,351.57 | 2,351.57 / 2,284.17 / 67.40 / 11,554.88 |
| 33 | 2027-06-25 | 2027-07-25 | 9,270.71 | 54.08 | 2,297.49 | 2,351.57 | 2,351.57 / 2,297.49 / 54.08 / 9,270.71 |
| 34 | 2027-07-25 | 2027-08-25 | 6,973.22 | 40.68 | 2,310.89 | 2,351.57 | 2,351.57 / 2,310.89 / 40.68 / 6,973.22 |
| 35 | 2027-08-25 | 2027-09-25 | 4,662.33 | 27.20 | 2,324.37 | 2,351.57 | 2,351.57 / 2,324.37 / 27.20 / 4,662.33 |
| 36 | 2027-09-25 | 2027-10-25 | 2,337.96 | 13.64 | 2,337.96 | 2,351.60 | 2,351.60 / 2,337.96 / 13.64 / 2,337.96 |

Montos iguales en las 12 filas. Interés total $1,041.46 y cuota $2,351.57, los mismos de `docs/REESTRUCTURACION.md`. La cuota 36 sale 3 centavos arriba porque su capital es el saldo restante. Las fechas van un mes después.

---

## La primera cuota nueva vence un mes después del inicio de pagos

El campo se llama «Nueva Fecha de Inicio de Pagos» ([`Reestructuracion.vue:151`](../../resources/js/Pages/Creditos/Reestructuracion.vue#L151)), pero el ciclo arranca en `i = 1` y vence en `fechaInicio->addMonths($i)` ([`ReestructuracionController.php:114`](../../app/Http/Controllers/ReestructuracionController.php#L114)). Lo medí en los 6:

| Crédito | Inicio de pagos capturado | Primera cuota en sistema | Desfase |
|---|---|---|---|
| 43 | 2026-10-25 | 2026-11-25 | 31 días |
| 2 | 2026-11-06 | 2026-12-06 | 30 días |
| 5 | 2026-10-21 | 2026-11-21 | 31 días |
| 22 | 2026-10-31 | 2026-12-01 | 31 días |
| 29 | 2026-11-15 | 2026-12-15 | 30 días |
| 30 | 2026-11-01 | 2026-12-01 | 30 días |

El reporte del desfase se confirma: un mes en todos. En `CreditService` el mismo `addMonths($i)` es correcto porque parte de la fecha de entrega, no de un pago. Las pruebas de la 4.6 no revisan `fecha_vencimiento` de las cuotas nuevas.

También cabe señalar el crédito 22: con inicio el día 31, `addMonths` desborda.

| Cuota | 10 | 11 | 12 | 13 | 14 | 15 |
|---|---|---|---|---|---|---|
| Vence | 2026-12-01 | 2026-12-31 | 2027-01-31 | 2027-03-03 | 2027-03-31 | 2027-05-01 |

Noviembre, febrero y abril quedan sin pago; diciembre y marzo tienen dos. El motor original tiene el mismo comportamiento (A.6), solo que ahí la fecha la pone la entrega y aquí el operador.

---

## `mora_condonada` es la mora del último cron, no la de la fecha de reestructura

`calcularBaseReestructuracion` suma `interes_moratorio_generado − interes_moratorio_pagado` tal como está guardado ([`ReestructuracionController.php:167-169`](../../app/Http/Controllers/ReestructuracionController.php#L167)). Ese valor lo escribe `crea:update-moratorio` con la fecha en que corre, no con `fecha_reestructura`.

| Crédito | Fecha reestructura | `mora_condonada` | Mora guardada | Regla del cron a la fecha | Diferencia | Rango |
|---|---|---|---|---|---|---|
| 43 | 2026-09-25 | 5,014.09 | 5,014.09 | 4,370.18 | +643.91 | Error |
| 2 | 2026-10-06 | 2,029.79 | 2,029.79 | 2,029.79 | 0.00 | Exacta |
| 5 | 2026-10-06 | 0.00 | 0.00 | 0.00 | 0.00 | Exacta |
| 22 | 2026-10-06 | 0.00 | 0.00 | 0.00 | 0.00 | Exacta |
| 29 | 2026-10-06 | 13,142.12 | 13,142.12 | 13,142.12 | 0.00 | Exacta |
| 30 | 2026-10-06 | 1,975.18 | 1,975.18 | 1,975.18 | 0.00 | Exacta |

Coincide cuando el cron corrió el mismo día de la reestructura. En el crédito 43, capturado con fecha 2026-09-25 el 2026-10-06, se condonan 11 días de mora que a esa fecha no existían.

El ejemplo de `docs/REESTRUCTURACION.md` tiene el mismo problema en el otro sentido. Sus $3,064.84 son la mora al 2026-08-31: la cuota 4 da `26,474.98 × 0.175 / 360 × 108 días = 1,389.94` y la cuota 8, que vence el 2026-09-15, aparece con $0. Al 2026-09-25 la regla del cron da $4,370.18, $1,305.34 más. La prueba T3 fija 3,064.84 porque lo pone como dato de entrada, así que no lo detecta.

Contra la base del Acuerdo (capital vencido de la cuota, A.10), la mora condonada sale muy arriba, porque el cron cobra sobre el saldo insoluto:

| Crédito | `mora_condonada` | Acuerdo a la fecha | Diferencia |
|---|---|---|---|
| 43 | 5,014.09 | 208.26 | +4,805.83 |
| 2 | 2,029.79 | 125.95 | +1,903.84 |
| 29 | 13,142.12 | 779.17 | +12,362.95 |
| 30 | 1,975.18 | 173.83 | +1,801.35 |

Como la mora se condona, no se le cobra al acreditado. Lo que queda inflado es el monto registrado como condonado.

---

## Respuestas al paso 6

| Punto | Resultado |
|---|---|
| ¿La primera cuota nueva vence en la fecha correcta? | No. En los 6 vence un mes después de la fecha de inicio de pagos (30 o 31 días) |
| ¿La numeración sigue consecutiva sin chocar? | Sí. En los 6 las nuevas van de `última + 1` a `última + plazo`, sin huecos ni duplicados (43: 25-36, 2: 19-36, 5: 25-48, 22: 10-15, 29: 19-54, 30: 13-24) |
| ¿Las cuotas viejas quedan en `Reestructurada` con `pago_restante = 0`? | Sí. 87 cuotas abiertas pasaron a `Reestructurada` con `pago_restante = 0`. Las `Pagado` no cambiaron de estado, `pago_restante` ni `capital_pagado` |
| ¿Σ `capital_esperado` de la tabla nueva = base? | Sí, diferencia $0.00 en los 6 |

---

## Interés pagado por adelantado en una cuota Parcial

El crédito 5 tenía la cuota 10 (vence 2027-01-21) en `Parcial`, con $858.45 de capital y $187.03 de interés ya pagados. El capital pagado sí se descuenta de la base. El interés no: la cuota no está vencida, así que no entra al devengado, y tampoco se abona a la base. El sistema y el script hacen lo mismo, porque la regla de la 4.6 no dice qué hacer con interés futuro ya cobrado.

| Concepto | Monto |
|---|---|
| Interés pagado de la cuota 10, sin efecto en la base | 187.03 |

---

## Lo que no pude determinar

- Si la intención de «inicio de pagos» es la fecha del primer pago o el día desde el que se cuenta el primer mes. Con la etiqueta actual, es lo primero.
- Si la mora condonada debe calcularse con la base del cron o con la del Acuerdo; depende de lo que se decida en la A.10.
- Qué hacer con el interés ya pagado de cuotas futuras al reestructurar.
- Cómo se comporta con créditos Sustentables con filas de gracia: en mi base no hay ninguno (A.6).
- Cómo se comporta un crédito reestructurado dos veces.
- Nada de esto está verificado en producción.

---

## Recomendación

Los montos de la 4.6 son correctos. Antes de cerrarla, Marco tendría que decidir el desfase de la primera cuota y si `mora_condonada` se calcula a `fecha_reestructura` en lugar de leer lo guardado (opinión: las dos, son cambios de una línea y de una función).
