# A.6 · Recálculo independiente del motor de amortización

---

## Resumen

Recalculé desde cero las tablas de amortización con un script en Python (`decimal`, sin librerías externas) y las comparé cuota por cuota contra lo guardado. Las fórmulas del motor son correctas. Lo que no coincide es la estructura de los créditos Sustentables: no tienen las tres filas de gracia que el motor debería generar.

| Métrica | Base local (datos de prueba) |
|---|---|
| Créditos comparados | 35 |
| Cuotas comparadas | 455 |
| Créditos que coinciden | 26 |
| Créditos con diferencias | 9 (todos Sustentables) |
| Diferencias de cálculo en `cuota_fija`, `capital_esperado`, `interes_ordinario_esperado`, `saldo_insoluto` | 0 |
| Créditos sintéticos generados con el motor y comparados | 2,000 (41,347 cuotas), 0 diferencias |

La base local tiene 35 créditos, no 2,000. El script corre igual sobre cualquier exportación.

---

## Cómo calculé

| Decisión | Qué hace el motor | Qué hace el script |
|---|---|---|
| Tasa | `tasa_interes_ordinario` es anual; `i = tasa / 100 / 12` ([`CreditService.php:24`](../../app/Services/CreditService.php#L24)) | Igual. Con `--tasa mensual` el script da 27 de 35 créditos distintos y 393 cuotas con diferencia mayor a $1.00, así que la interpretación anual es la correcta |
| Cuota | `C = S · i / (1 − (1+i)^−n)`, sin redondear; con tasa 0, `S / n` ([`CreditService.php:46`](../../app/Services/CreditService.php#L46)) | Igual, con `Decimal` de 40 dígitos |
| Redondeo | `round()` de PHP a 2 decimales en interés, capital y cuota | `ROUND_HALF_UP` a 2 decimales en los mismos tres puntos |
| Interés | `round(saldo * i, 2)` | `round(saldo * tasa / 12, 2)`: multiplica antes de dividir |
| Cuota guardada | `cuota_fija = round(capital + interés, 2)`, no la cuota teórica | Igual |
| Última cuota | El capital es el saldo restante, `round($saldo, 2)` ([`CreditService.php:53`](../../app/Services/CreditService.php#L53)); la cuota final absorbe el redondeo | Igual |
| Gracia | Modalidad que contenga «sustentable»: 3 filas con `numero_cuota` −3, −2, −1, saldo igual al monto y todo lo demás en 0 | Igual; las filas ausentes se reportan como diferencia de estructura |
| Fechas | `addMonths(i + gracia)` con desborde de fin de mes | Igual; también las comparé |

Al probar con 2,000 créditos sintéticos, la primera versión del script dio 3 créditos con un centavo de diferencia. Era un error del script: `0.037/12` no es exacto en `Decimal` y el interés de $56.425 caía del lado equivocado. Al multiplicar antes de dividir desaparecieron.

---

## Resultado sobre la base local

| Campo | Exactas | Explicadas por pago | Redondeo (≤ 0.01) | Menor (≤ 1.00) | Mayor (> 1.00) |
|---|---|---|---|---|---|
| `cuota_fija` | 455 | 0 | 0 | 0 | 0 |
| `capital_esperado` | 455 | 0 | 0 | 0 | 0 |
| `interes_ordinario_esperado` | 442 | 13 | 0 | 0 | 0 |
| `saldo_insoluto` | 337 | 118 | 0 | 0 | 0 |

«Explicada por pago» es una diferencia que el registro de un pago produce a propósito:

| Campo | Qué pasa | Cuántas |
|---|---|---|
| `saldo_insoluto` | Al pagar, el saldo de la fila baja en `capital_pagado` ([`TestCreditosSeeder.php:343`](../../database/seeders/TestCreditosSeeder.php#L343)). Guardado + `capital_pagado` es igual al saldo recalculado en las 118 cuotas pagadas o parciales | 118 |
| `interes_ordinario_esperado` | En una liquidación anticipada, el interés esperado de las cuotas futuras se iguala al pagado ([`TestCreditosSeeder.php:312`](../../database/seeders/TestCreditosSeeder.php#L312)) | 13 |

Por eso `saldo_insoluto` de una cuota pagada ya no es el saldo de la tabla original. Quien lo lea como saldo de apertura de esa cuota lo leerá mal.

La última cuota difiere de la cuota fija hasta $0.22 en los 2,000 créditos sintéticos (en 276 es idéntica, en 1,248 difiere 5 centavos o menos).

---

## Crédito desarrollado a mano: crédito 1

$20,000.00, 12 meses, 7% anual, modalidad Emprendedores, entrega 2026-06-21. `i = 0.07 / 12 = 0.0058333…`. Cuota teórica: `20,000 × i / (1 − (1+i)^−12) = 1,730.5349…`.

| Cuota | Saldo inicial | Interés (saldo × i) | Capital (1,730.5349 − interés) | Cuota | Saldo final | Cuota en sistema | Saldo en sistema |
|---|---|---|---|---|---|---|---|
| 1 | 20,000.00 | 116.67 | 1,613.86 | 1,730.53 | 18,386.14 | 1,730.53 | 18,386.14 (pagada) |
| 2 | 18,386.14 | 107.25 | 1,623.28 | 1,730.53 | 16,762.86 | 1,730.53 | 16,762.86 (pagada) |
| 3 | 16,762.86 | 97.78 | 1,632.75 | 1,730.53 | 15,130.11 | 1,730.53 | 16,762.86 |
| 4 | 15,130.11 | 88.26 | 1,642.27 | 1,730.53 | 13,487.84 | 1,730.53 | 15,130.11 |
| 5 | 13,487.84 | 78.68 | 1,651.85 | 1,730.53 | 11,835.99 | 1,730.53 | 13,487.84 |
| 6 | 11,835.99 | 69.04 | 1,661.49 | 1,730.53 | 10,174.50 | 1,730.53 | 11,835.99 |
| 7 | 10,174.50 | 59.35 | 1,671.18 | 1,730.53 | 8,503.32 | 1,730.53 | 10,174.50 |
| 8 | 8,503.32 | 49.60 | 1,680.93 | 1,730.53 | 6,822.39 | 1,730.53 | 8,503.32 |
| 9 | 6,822.39 | 39.80 | 1,690.73 | 1,730.53 | 5,131.66 | 1,730.53 | 6,822.39 |
| 10 | 5,131.66 | 29.93 | 1,700.60 | 1,730.53 | 3,431.06 | 1,730.53 | 5,131.66 |
| 11 | 3,431.06 | 20.01 | 1,710.52 | 1,730.53 | 1,720.54 | 1,730.53 | 3,431.06 |
| 12 | 1,720.54 | 10.04 | 1,720.54 | 1,730.58 | 0.00 | 1,730.58 | 1,720.54 |

Interés, capital y cuota coinciden en las 12 filas. La columna «Saldo en sistema» es el saldo inicial de cada cuota, salvo en las dos pagadas, donde ya se descontó su capital. La cuota 12 sale 5 centavos arriba porque el capital final es el saldo restante.

---

## Los 9 créditos Sustentables no tienen filas de gracia

| Generador | Crea filas de gracia | Detecta Sustentable |
|---|---|---|
| [`CreditService.php:28`](../../app/Services/CreditService.php#L28) (vía solicitud operativa) | Sí | `strtolower`, sin distinguir mayúsculas |
| [`AcreditadoController.php:165`](../../app/Http/Controllers/AcreditadoController.php#L165) | No, solo corre las fechas 3 meses | `str_contains` con mayúscula exacta |
| [`CobranzaJuridicaController.php:166`](../../app/Http/Controllers/CobranzaJuridicaController.php#L166) | No, solo corre las fechas 3 meses | `str_contains` con mayúscula exacta |
| [`ReestructuracionController.php:94`](../../app/Http/Controllers/ReestructuracionController.php#L94) | No, sin prórroga | No aplica |
| Seeders de prueba | No, solo corren las fechas | Mayúscula exacta |

Los 9 créditos Sustentables de la base local (3, 14, 15, 16, 17, 18, 28, 30, 32) carecen de las filas −3, −2 y −1. Las fechas de vencimiento sí coinciden, porque los generadores sin filas de gracia igual suman 3 meses. Los 26 créditos Artesanales y Emprendedores coinciden en todo.

El patrón es la modalidad: 9 de 9 Sustentables con diferencia, 0 de 26 en las otras dos. Lo explica que la tabla se construye en cuatro lugares con la misma fórmula copiada, y solo uno crea la gracia.

---

## Lo que no pude determinar

- Qué generador creó los 9 créditos Sustentables: lo deduje de los seeders, no de un registro.
- Si hay créditos con `tasa_interes_ordinario` capturada como mensual. El script solo prueba la interpretación anual.
- Si hay créditos reestructurados que fallen. Su segunda tabla arranca en `ultimaCuota + 1` y el script no la modela; en la base local hay 0 cuotas `Reestructurada`.
- Si alguna tabla fue editada a mano después de generarse.
- Si hay plazos o tasas fuera de los rangos que probé (3 a 36 meses, tasa 0 a 15%).

---

## Recomendación

Decidir si las filas de gracia se generan en cada controlador o solo en `CreditService` (opinión: lo segundo, llamándolo desde los cuatro lugares).
