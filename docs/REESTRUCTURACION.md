# REESTRUCTURACIÓN — Anatocismo en la base de cálculo

Ticket: `docs/crea-reestructuracion-anatocismo`

Al reestructurar un crédito, `ReestructuracionController::store()` construye la nueva
deuda a partir de la suma de `pago_restante` de las cuotas activas. Ese campo equivale a
`capital_esperado + interes_ordinario_esperado − pagado`, por lo que **incluye el interés
que todavía no se ha devengado** (cuotas futuras). Sobre esa base el sistema genera un
plan nuevo que **vuelve a calcular interés**, de modo que se cobra interés sobre el
interés futuro del plan anterior: anatocismo.

**Regla autorizada por dirección:** la base de una reestructuración es
**capital pendiente + interés ordinario ya devengado** (no el esperado/futuro). Todo eso
se capitaliza y sobre ese monto se genera la tabla de amortizaciones desde cero.

---

## 1. Mapa de escritores y lectores de `pago_restante`

| Archivo:línea | Lee/Escribe | Qué hace |
|---|---|---|
| `app/Services/CreditService.php:63` | Escribe | Inicializa `pago_restante` = cuota al generar la tabla de un crédito nuevo |
| `app/Http/Controllers/AcreditadoController.php:188` | Escribe | Inicializa `pago_restante` = cuota al crear el crédito |
| `app/Http/Controllers/PagoController.php:247-251` | Escribe | Al aplicar un pago recalcula `pago_restante = capital_esperado + interes_ordinario_esperado − (capital_pagado + interes_ordinario_pagado)` |
| `app/Http/Controllers/PagoController.php:430-434,488-492` | Escribe | Recalcula `pago_restante` al aplicar un abono a capital (Reducir Plazo / Reducir Cuota) |
| `app/Http/Controllers/PagoController.php:533,564-568` | Escribe | Al cancelar un pago restaura `pago_restante` (snapshot o reversión) |
| `app/Http/Controllers/ReestructuracionController.php:66-68` | Lee | **Base de la reestructuración** = `sum('pago_restante')` ← origen del defecto |
| `app/Http/Controllers/ReestructuracionController.php:75` | Escribe | Guarda `saldo_al_momento` con esa base |
| `app/Http/Controllers/ReestructuracionController.php:87-89` | Escribe | Marca las cuotas viejas como `Reestructurada` y `pago_restante = 0` |
| `app/Http/Controllers/ReestructuracionController.php:115` | Escribe | Inicializa `pago_restante` = cuota en las cuotas nuevas |
| `app/Http/Controllers/ReestructuracionController.php:21-23` | Lee | Pantalla `create()`: `saldo_pendiente` para la vista previa |
| `resources/js/Pages/Creditos/Reestructuracion.vue:48-53` | Lee | Vista previa "Nuevo Capital a Reestructurar" |

### Observaciones

- `pago_restante` es un **snapshot que incluye el interés de todas las cuotas**, ya
  devengadas o no. Se consume correctamente mes a mes cuando el crédito sigue su curso,
  pero al capturarlo en una reestructuración incluye interés que aún no existe como deuda
  exigible.
- La liquidación anticipada **sí** maneja bien este punto: `PagoController.php:201-205`
  condona el interés de las cuotas futuras. La reestructuración no tiene un bloque
  equivalente.

---

## 2. El defecto en el código

| Archivo:línea | Qué hace |
|---|---|
| `ReestructuracionController.php:66-68` | La base incluye el interés futuro, porque `pago_restante` = capital + interés esperado − pagado |
| `ReestructuracionController.php:91` | `monto = saldoPendiente − mora_condonada − interes_condonado` (sin validar > 0) |
| `ReestructuracionController.php:96-98` | Calcula la cuota sobre ese monto ya inflado |
| `ReestructuracionController.php:103-105` | `$saldo = $monto;` y en cada mes `$interes = round($saldo * $tasa, 2)` → se cobra interés sobre el interés futuro |
| `ReestructuracionController.php:108-121` | Queda como `capital_esperado`, `cuota_fija` y `pago_restante` de las cuotas nuevas |

No existe en el repo ninguna línea que nombre el anatocismo ni que condone el interés
futuro en una reestructuración. El sobrecosto ocurre **por omisión**: la base lo incluye y
nada lo separa.

---

## 3. Ejemplo numérico real (CREA-2026-101)

Crédito id interno **36**, modalidad Emprendedores, $30,000 a 24 meses, tasa ordinaria
7%, tasa moratoria 17.50%, entrega 15/01/2026. Fecha de corte: **25/09/2026**. Base de
datos `iyem_crea_test`.

| Concepto | Fórmula | Monto |
|---|---|---|
| Capital pendiente (X) | Σ (`capital_esperado − capital_pagado`) | **$26,474.98** |
| Interés total pendiente | Σ (`interes_ordinario_esperado − interes_ordinario_pagado`) | $1,731.75 |
| — Interés ya devengado | cuotas con `fecha_vencimiento <= 25/09/2026` (4–8) | $702.44 |
| — Interés futuro no devengado (Y) | cuotas con `fecha_vencimiento > 25/09/2026` (9–24) | **$1,029.31** |
| Mora generada | Σ `interes_moratorio_generado` | $3,064.84 |

| Base | Cálculo | Monto |
|---|---|---|
| **Base que usa hoy el sistema** (Σ `pago_restante`) | X + Y | **$28,206.73** |
| **Base que debería usar** (regla autorizada) | X + devengado | **$27,177.42** |
| **Diferencia en pesos** | Y | **$1,029.31** |

El interés futuro de $1,029.31, al recapitalizarse y recobrarse al 7%, genera
aproximadamente **$39.45 de interés adicional a 12 meses** o **$76.72 a 24 meses**:

| Base del nuevo plan | Cuota (7%, 12m) | Interés total |
|---|---|---|
| $27,177.42 (correcta) | $2,351.57 | $1,041.46 |
| $28,206.73 (actual) | $2,440.64 | $1,080.91 |
| Diferencia | | **+$39.45** |

### Reproducción

Selección del crédito y agregados:

```sql
SELECT
  ROUND(SUM(a.capital_esperado - a.capital_pagado),2) AS capital_pend,
  ROUND(SUM(a.interes_ordinario_esperado - a.interes_ordinario_pagado),2) AS interes_total,
  ROUND(SUM(CASE WHEN a.fecha_vencimiento <= CURRENT_DATE
                 THEN a.interes_ordinario_esperado - a.interes_ordinario_pagado
                 ELSE 0 END),2) AS interes_devengado,
  ROUND(SUM(CASE WHEN a.fecha_vencimiento > CURRENT_DATE
                 THEN a.interes_ordinario_esperado - a.interes_ordinario_pagado
                 ELSE 0 END),2) AS interes_futuro,
  ROUND(SUM(a.pago_restante),2) AS base_actual,
  ROUND(SUM(a.interes_moratorio_generado),2) AS mora_generada
FROM amortizaciones a
WHERE a.credito_id = 36
  AND a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia');
```

Resultado: `capital_pend = 26474.98`, `interes_total = 1731.75`,
`interes_devengado = 702.44`, `interes_futuro = 1029.31`,
`base_actual = 28206.73`, `mora_generada = 3064.84`.

Detalle por cuota:

```sql
SELECT a.numero_cuota, a.fecha_vencimiento, a.estado, a.saldo_insoluto,
       a.capital_esperado, a.capital_pagado,
       a.interes_ordinario_esperado, a.interes_ordinario_pagado,
       a.pago_restante, a.interes_moratorio_generado
FROM amortizaciones a
WHERE a.credito_id = 36
ORDER BY a.numero_cuota;
```

Fórmula de la simulación (la del controlador, `ReestructuracionController.php:96-98`):

```
i = tasa_anual / 100 / 12
cuota = monto × i / (1 − (1 + i)^(−n))
interés_total = (cuota × n) − monto
```

---

## 4. Corrección propuesta

**Base = capital pendiente + interés ordinario ya devengado**, medido contra
`fecha_reestructura`:

```
capital_pendiente = Σ (capital_esperado − capital_pagado)             [cuotas activas]
interes_devengado = Σ (interes_ordinario_esperado − interes_ordinario_pagado)
                     solo cuotas con fecha_vencimiento <= fecha_reestructura
monto = capital_pendiente + interes_devengado
```

El interés futuro del plan anterior ya **no** entra a la base: se condona o se informa por
separado, igual que en la liquidación anticipada (`PagoController.php:201-205`).

Además, se debe agregar la validación faltante en `ReestructuracionController.php:91`:
`monto > 0` antes de generar la nueva tabla.

---

## 5. Hallazgo secundario: `mora_condonada` se resta del capital

La pantalla precarga `mora_condonada` con la mora acumulada
(`resources/js/Pages/Creditos/Reestructuracion.vue:35`) y `ReestructuracionController.php:91`
la resta de la base:

```
monto = saldoPendiente − mora_condonada − interes_condonado
```

Pero `pago_restante` **no incluye mora** (`PagoController.php:247-251`). Por lo tanto se
está restando mora de una base que no la contiene:

```
monto = 28,206.73 − 3,064.84 − 0 = $25,141.89   (default de la UI)
```

Ese monto queda **$1,333.09 por debajo del capital real** ($26,474.98): se perdona capital
sin resolutivo que lo autorice.

| Escenario | Base del nuevo plan | Efecto |
|---|---|---|
| Condonaciones en cero | $28,206.73 | Incluye interés futuro → anatocismo |
| Default de la pantalla | $25,141.89 | Resta mora del capital → subestima la deuda |
| Regla autorizada | $27,177.42 | Capital + interés devengado |

Con la regla autorizada, `mora_condonada` deja de restarse del capital y queda solo como
registro (la mora nunca formó parte de la base).

---

## 6. Revisión normativa

- `public/formatos/RO.pdf` (Reglas de Operación, Acuerdo IYEM 03/2025): se extrajo el
  texto de sus 350 flujos de contenido. "Reestructuración" aparece una sola vez, dentro de
  una **lista genérica de destinos permitidos del crédito** ("reorganización,
  reestructuración, disolución, liquidación o cualquier otra"). No define fórmula de
  reestructuración ni menciona anatocismo.
- `resources/views/pdf/contrato-credito.blade.php`: no contiene cláusula de
  reestructuración ni de capitalización de intereses vencidos.
- La regla aplicable fue confirmada por dirección: **capital pendiente + interés ordinario
  devengado, capitalizado**.

---

## 7. Pregunta pendiente

`interes_condonado` (`ReestructuracionController.php:91`) todavía no tiene tratamiento
definido bajo la nueva regla. Falta confirmar con dirección:

> ¿Conservamos `interes_condonado` como descuento opcional del interés devengado, o todo
> se capitaliza sin descuentos?

Hasta que se responda, la base es `capital + devengado` sin restar `interes_condonado`.
