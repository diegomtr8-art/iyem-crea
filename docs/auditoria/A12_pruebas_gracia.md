# A.12 · Pruebas de la gracia de Sustentable en los generadores de la tabla

---

## Resumen

El A.6 dedujo, leyendo código, que de los generadores de la tabla de amortización solo `CreditService` crea las filas de gracia de Sustentable. [`tests/Feature/GeneradoresTablaGraciaTest.php`](../../tests/Feature/GeneradoresTablaGraciaTest.php) lo demuestra con el mismo caso en cada generador: $20,000, 12 meses, 7 %, entrega 2026-01-15.

| Prueba | Resultado |
|---|---|
| Pasan | 6 |
| En skip por el hallazgo A.6 | 6 |
| Fallan sin el skip | 6, todas con `actual size 0 matches expected size 3` (cero filas de gracia) |

Una tabla de Sustentable correcta tiene 3 filas `Gracia` con `numero_cuota` −3, −2 y −1, `saldo_insoluto` igual al monto y capital, interés, cuota y `pago_restante` en 0; además, 12 cuotas normales (1 a 12) cuya primera vence 4 meses después de la entrega, y la suma de `capital_esperado` igual al monto. Una tabla Artesanal no tiene filas de gracia, y su primera cuota vence un mes después de la entrega.

---

## Resultado por generador y modalidad

| Generador | Modalidad | Resultado | Por qué |
|---|---|---|---|
| [`CreditService.php:25`](../../app/Services/CreditService.php#L25) | Sustentable | Pasa | Crea las 3 filas de gracia y recorre las fechas 3 meses |
| | sustentable | Pasa | Compara con `strtolower` |
| | SUSTENTABLE | Pasa | Igual |
| | Artesanal | Pasa | Sin gracia, primera cuota al mes |
| [`AcreditadoController.php:166`](../../app/Http/Controllers/AcreditadoController.php#L166) | Sustentable | Falla (skip) | 0 filas de gracia. Las fechas sí se recorren 3 meses: primera cuota el 2026-05-15 |
| | sustentable | Falla (skip) | 0 filas de gracia. `str_contains` con mayúscula exacta no lo reconoce: primera cuota el 2026-02-15, sin prórroga |
| | SUSTENTABLE | Falla (skip) | Igual que `sustentable` |
| | Artesanal | Pasa | |
| [`CobranzaJuridicaController.php:166`](../../app/Http/Controllers/CobranzaJuridicaController.php#L166) | Sustentable | Falla (skip) | 0 filas de gracia; fechas recorridas 3 meses |
| | sustentable | Falla (skip) | 0 filas de gracia y sin prórroga, por la misma comparación con mayúscula exacta |
| | SUSTENTABLE | Falla (skip) | Igual que `sustentable` |
| | Artesanal | Pasa | |

En los cuatro casos que fallan, la prueba se detiene en la primera aserción: el conteo de filas de gracia. Leyendo el código, las variantes en minúsculas y en mayúsculas también fallarían en la fecha de la primera cuota.

`ReestructuracionController` no está en la prueba porque no crea créditos: rehace la tabla de uno existente sin prórroga de gracia, igual que lo marcaba el A.6.

Para reactivar una prueba se quita `->skip(GRACIA_SKIP)` en su `test(...)`. Una vez corregido el generador, debe pasar sin cambiar nada más.

---

## Cómo reproducir cada falla

### AcreditadoController (alta directa de expediente)

1. Entrar como usuario operativo y abrir Acreditados, Nuevo (`GET /acreditados/create`, envía a `POST /acreditados`).
2. Capturar: modalidad cuyo nombre sea `Sustentable` (o `sustentable`, `SUSTENTABLE`), monto 20000, plazo 12, fecha de entrega 2026-01-15. La tasa la toma de la modalidad (7 %).
3. Guardar y revisar la tabla del acreditado: no aparecen las filas −3, −2, −1. Con `Sustentable` la primera cuota vence el 2026-05-15; con las otras dos escrituras, el 2026-02-15.

### CobranzaJuridicaController (reestructura desde jurídico)

Sus rutas están comentadas en [`routes/web.php:184`](../../routes/web.php#L184), así que hoy no hay pantalla para llegar. Solo se reproduce con la prueba o desde tinker:

1. Crédito Sustentable sin tabla, con un `ExpedienteJuridico` en estatus `Pendiente`.
2. Llamar `CobranzaJuridicaController@update` con `estatus=Reestructurada`, `nuevo_monto=20000`, `nuevo_plazo_meses=12` y `nueva_fecha_inicio=2026-01-15`.
3. La tabla nueva tiene 12 cuotas y ninguna fila de gracia.

### CreditService (referencia, pasa)

Es el generador de Solicitudes, Registrar crédito (`POST /solicitudes/{solicitud}/registrar-credito`, [`SolicitudOperativoController.php:460`](../../app/Http/Controllers/SolicitudOperativoController.php#L460)). Con los mismos datos crea las 3 filas de gracia.

---

## Condiciones del entorno de pruebas

| Problema | Efecto | Qué hice |
|---|---|---|
| [`2026_08_17_000003_add_gracia_...php`](../../database/migrations/2026_08_17_000003_add_gracia_to_amortizaciones_and_observaciones.php) usa `MODIFY COLUMN`, que SQLite no entiende | En `develop` ninguna prueba con `RefreshDatabase` llega a correr | Validé con el arreglo de `fix/crea-migracion-gracia-sqlite` aplicado de forma temporal; no forma parte de esta rama |
| En SQLite el CHECK de `amortizaciones.estado` no incluye `Reestructurada` ni `Gracia` | Cualquier fila `Gracia` truena aunque el generador esté bien | La prueba activa `PRAGMA ignore_check_constraints` en `beforeEach` y lo apaga en `afterEach` |
| `ciudadanoConCredito()` está declarada en `Portal/ConstanciaCreditoTest.php` y en `Portal/TextosPortalTest.php` | `php artisan test` aborta con un error fatal antes de correr nada | Sin tocar; son pruebas de otros |

Con el arreglo de migraciones aplicado y sin `tests/Feature/Portal`, la suite queda en 12 fallas, 6 skipped y 144 pasadas. Ninguna de esas fallas es de este archivo. `CreditServiceTest` 5 falla por el mismo CHECK, `ReestructuracionTest` T1, T3 y T4 por `Reestructurada`, y `CreditServiceTest` 6 a 8, `RegistrationTest`, `CarteraActivaTest` y `DashboardTest` por motivos propios.

---

## Lo que no pude determinar

- Si en MySQL las 6 pruebas que pasan se comportan igual: corrieron en SQLite con el CHECK desactivado.
- Si el módulo de Cobranza Jurídica se va a reactivar; mientras sus rutas sigan comentadas, ese generador no lo usa nadie.
- Por qué falla `CreditServiceTest` 6 (fecha de la primera cuota); no es parte de este reporte.

---

## Recomendación

Que `AcreditadoController` y `CobranzaJuridicaController` llamen a `CreditService::generarTablaAmortizacion()` en lugar de su copia del cálculo, y quitar los skips para comprobarlo. Opinión: el arreglo de la migración en SQLite tiene que entrar a `develop` antes, o ninguna de estas pruebas corre en la integración.
