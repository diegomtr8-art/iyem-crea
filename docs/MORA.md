# MORA — Columnas duplicadas en `amortizaciones`

Ticket: `fix/crea-columnas-mora`

La mora se persiste en dos columnas distintas de `amortizaciones` y cada una la
escribe un proceso diferente. La liquidación anticipada del portal
(`app/Http/Controllers/Portal/EstadoCuentaController.php:80`) lee solo
`moratorio_acumulado`, por lo que puede no incluir la mora generada al aplicar
pagos. Este documento mapea quién escribe y quién lee cada columna, mide la
divergencia con datos reales y recomienda la fuente única.

---

## 1. Mapa de escritores y lectores

### `interes_moratorio_generado`

| Archivo:línea | Lee/Escribe | Qué hace |
|---|---|---|
| `app/Services/CreditService.php:41` | Escribe | Inicializa en 0 la mora de cada cuota de gracia al generar la tabla |
| `app/Services/CreditService.php:68` | Escribe | Inicializa en 0 la mora de cada cuota normal al generar la tabla |
| `app/Services/CreditService.php:97` | Escribe | `actualizarMoraDeCuota`: persiste `pago_restante × tasaDiaria × días` (hoy solo invocado desde tests) |
| `app/Console/Commands/UpdateMoratorio.php:39` | Escribe | Cron diario 08:00: `(saldo_insoluto − capital_pagado) × tasaDiaria × días` |
| `app/Http/Controllers/PagoController.php:244` | Escribe | Al aplicar pago, guarda la mora de la cuota = `(saldo_insoluto − capital_pagado) × tasaDiaria × días` |
| `app/Http/Controllers/PagoController.php:188` | Lee | Snapshot previo de la cuota (para reversión al cancelar) |
| `app/Http/Controllers/PagoController.php:490` | Escribe | Al cancelar el pago, restaura el valor del snapshot |
| `app/Http/Controllers/CreditoController.php:43` | Lee | Suma la mora a condonar (condonación total por fallecimiento/causa social) |
| `app/Http/Controllers/CreditoController.php:48` | Escribe | Resetea a 0 la mora de la cuota condonada |
| `app/Http/Controllers/CobranzaJuridicaController.php:199` | Escribe | Inicializa en 0 la mora de las cuotas nuevas por prórroga judicial |
| `app/Http/Controllers/AcreditadoController.php:193` | Escribe | Inicializa en 0 la mora de las cuotas nuevas al crear/reestructurar crédito |
| `app/Http/Controllers/ReestructuracionController.php:120` | Escribe | Inicializa en 0 la mora de las cuotas nuevas de la reestructuración |
| `app/Http/Controllers/PublicController.php:75` | Lee | Expone `moratorio` en la tabla pública por CURP |
| `app/Models/Amortizacion.php:27` | Declaración | `$fillable` |
| `database/migrations/2026_04_15_175901_add_moratorio_generado_to_amortizaciones_table.php:16` | Esquema | Crea `decimal(12,2) default 0` (`after(interes_moratorio_pagado)`) |
| `database/seeders/TestDataSeeder.php:277` | Escribe | Fixture: inicializa en 0 |
| `database/seeders/TestCreditosSeeder.php:229` | Escribe | Fixture: inicializa en 0 |
| `database/seeders/TestCreditosSeeder.php:342` | Escribe | Fixture: simula pago y escribe la mora calculada |
| `database/seeders/Add30CreditosSeeder.php:219` | Escribe | Fixture: inicializa en 0 |
| `database/seeders/Add30CreditosSeeder.php:309` | Escribe | Fixture: simula pago y escribe la mora calculada |
| `tests/Feature/PagoSobranteParcialTest.php:56` | Escribe | Fixture de test: inicializa en 0 |
| `tests/Feature/PagoSobranteOpcionesTest.php:55,83,294,344` | Escribe | Fixture de test: inicializa en 0 |
| `tests/Unit/MoraTest.php` | Indirecto | Ejercita `actualizarMoraDeCuota`; no referencia la columna directamente |

### `moratorio_acumulado`

| Archivo:línea | Lee/Escribe | Qué hace |
|---|---|---|
| `app/Console/Commands/UpdateMoratorio.php:40` | Escribe | Cron: espeja **el mismo** valor que `interes_moratorio_generado` |
| `app/Http/Controllers/CondonacionFormalController.php:23` | Lee | Suma la mora pendiente para el saldo a condonar formalmente |
| `app/Http/Controllers/CondonacionFormalController.php:77` | Escribe | En condonación total, resetea a 0 la mora de las cuotas |
| `app/Http/Controllers/PagoController.php:189` | Lee | Snapshot previo de la cuota (para reversión al cancelar) |
| `app/Http/Controllers/PagoController.php:491` | Escribe | Al cancelar el pago, restaura el valor del snapshot |
| `app/Http/Controllers/Portal/EstadoCuentaController.php:80` | Lee | **Liquidación anticipada**: suma la mora vencida ← origen del bug |
| `app/Http/Controllers/Portal/MiCreditoController.php:106` | Lee | Portal ciudadano: campo `moratorio` de la tabla del crédito |
| `app/Http/Controllers/ReestructuracionController.php:27` | Lee | Suma la mora acumulada para la pantalla de reestructuración |
| `resources/views/pdf/estado-cuenta.blade.php:135` | Lee | Muestra la mora en el PDF de estado de cuenta |
| `app/Models/Amortizacion.php:29` | Declaración | `$fillable` |
| `database/migrations/2026_04_13_230814_create_amortizaciones_table.php:34` | Esquema | Crea `decimal(15,2) default 0` (columna original, "Se actualiza por Job o al ver el Show") |
| `tests/Feature/PagoSobranteParcialTest.php:57` | Escribe | Fixture de test: inicializa en 0 |
| `tests/Feature/PagoSobranteOpcionesTest.php:56,84,295,345` | Escribe | Fixture de test: inicializa en 0 |
| `CHANGELOG_AUDITORIA.md:24-26` | Doc | Registra el hotfix P0 previo: "`moratorio_acumulado` nunca se sincronizaba" |

### Observaciones

- **`PagoController` es el único punto que escribe mora y NO espeja
  `moratorio_acumulado`** (líneas 244 vs. 189/491): ahí nace la divergencia.
- Los seeders `TestCreditosSeeder:342` y `Add30CreditosSeeder:309` replican el
  mismo patrón (escriben solo `interes_moratorio_generado`), por eso los datos
  de prueba también divergen.
- `moratorio_acumulado` (migración `04_13`) es la columna **original**;
  `interes_moratorio_generado` (`04_15`) se agregó después como "mora calculada
  al día de hoy". La fuente correcta llegó después y no se terminó de migrar.

---

## 2. Medición de divergencia (datos reales)

Consulta ejecutada contra la base `iyem_crea_test`:

```sql
SELECT credito_id, numero_cuota, moratorio_acumulado, interes_moratorio_generado
FROM amortizaciones
WHERE moratorio_acumulado <> interes_moratorio_generado
LIMIT 50;
```

Resultado:

- Filas totales en `amortizaciones`: **507**
- Filas que difieren: **3**
- Diferencia máxima: **$850.69**

| credito_id | numero_cuota | estado | moratorio_acumulado | interes_moratorio_generado |
|---|---|---|---|---|
| 2 | 1 | Pagado | 0.00 | 850.69 |
| 2 | 2 | Pagado | 0.00 | 741.28 |
| 26 | 1 | Pagado | 0.00 | 641.67 |

Las tres son cuotas `Pagado` con `moratorio_acumulado = 0` e
`interes_moratorio_generado > 0`: exactamente el patrón del bug (mora generada
y cobrada por un pago, nunca espejada a `moratorio_acumulado`).

---

## 3. Recomendación: fuente única = `interes_moratorio_generado`

1. **Los casos divergentes prueban dónde vive la verdad.** En todos,
   `moratorio_acumulado = 0` e `interes_moratorio_generado > 0`, y son cuotas
   `Pagado`. Cuando un pago generó y cobró mora, esta quedó registrada en
   `interes_moratorio_generado`; `moratorio_acumulado` nunca se enteró.
2. **Cobertura de escritores.** `interes_moratorio_generado` la escriben los tres
   procesos que calculan mora (cron `UpdateMoratorio`, `CreditService`,
   `PagoController`). `moratorio_acumulado` solo la escribe el cron, por lo que
   por diseño no puede reflejar mora nacida de un pago.
3. **Consumidores de negocio ya la usan.** Condonación
   (`CreditoController.php:43`) y el portal público (`PublicController.php:75`)
   ya leen `interes_moratorio_generado`. Es de menor riesgo migrar los 5 lectores
   de `moratorio_acumulado` que reescribir todos los generadores.
4. **Semántica.** `interes_moratorio_generado` es explícita ("mora generada al
   día de hoy"); `moratorio_acumulado` nació como campo dinámico legacy sin
   dueño claro, y por eso derivó.

### Tratamiento de la columna perdedora

`moratorio_acumulado` **no se borra ni se congela**: se conserva el histórico y
se mantiene como espejo sincronizado (doble escritura), marcada `@deprecated`
en el modelo. Así la consulta de divergencia queda en 0 para los créditos nuevos
(criterio de aceptación) sin romper consumidores externos.

### Fórmula

Unificar la base de cálculo a `saldo_insoluto − capital_pagado` (la que ya usan
el cron y `PagoController`). `CreditService` usa `pago_restante`, que sería un
tercer origen de divergencia aunque las columnas ya coincidan.
