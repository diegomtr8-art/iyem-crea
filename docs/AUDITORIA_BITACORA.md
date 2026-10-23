# Bitácora de auditoría

CREA guarda en la tabla `auditoria_log` quién hizo qué operación, cuándo y desde qué IP. Las filas se escriben con `AuditoriaLog::registrar(...)`, ya sea desde los observers de `Credito` y `Pago` o con llamadas directas en algunos controladores. Se consultan en la pantalla **Bitácora Auditoría** (`/auditoria`).

Este documento dice qué queda registrado, qué no, y cómo se comprobó (ticket J.11).

**Cómo se revisó.**
- **Diagnóstico:** se hizo sobre `develop` (commit `3a66703`, 13/10/2026). Cada operación se ejecutó en una base de prueba y después se leyó lo que quedó en `auditoria_log`. El punto del `enum` se probó en MySQL 8.0 con el mismo modo estricto que usa Laravel.
- **Correcciones:** se hicieron sobre `develop` (`4fb46b8`, 22/10/2026). Los números de línea corresponden a esta versión.
- **Pruebas:** cada operación tiene una en `tests/Feature/AuditoriaBitacoraTest.php` (sección 8).

---

## 1. Qué queda registrado

Cada fila trae **entidad, id, acción, campo, valor anterior, valor nuevo, usuario, IP y descripción**. La descripción lleva la clave del crédito y los montos con formato `$1,234.56`.

| Operación | Fila | Campo: antes → después | La descripción dice | Dónde está la llamada |
|---|---|---|---|---|
| Registrar pago | Pago · created | `monto_recibido`: ∅ → 2076.61 | Folio, clave, monto, forma de pago y lo aplicado a mora, interés y capital | `PagoObserver.php:17`. El pago se crea en `PagoController.php:289` |
| Reducir plazo / reducir cuota | Además, Credito · updated | `plan_de_pagos`: «12 cuotas pendientes, la siguiente de $2,000.00» → «8 cuotas pendientes, la siguiente de $2,000.00» | Clave, tipo de abono, folio y cuánto se abonó a capital | `PagoObserver.php:66`. El antes sale del *snapshot* que el pago guarda para poder cancelarse, así que no se tocó `PagoController` |
| Cancelar pago | Pago · updated | `cancelado`: Activo → Cancelado | Folio, monto, clave y motivo | `PagoObserver.php:38`. Cancela `PagoController.php:634` |
| Cambio de estatus del crédito | Credito · updated | `estatus`: Activo → Moroso | Clave y el cambio | `CreditoObserver.php:52`. Lo disparan pagos, cancelaciones, condonaciones y reestructuración |
| Condonación total (`CreditoController::condonar`) | Credito · updated | `saldo_condonado`: 12450.30 → 0.00 | Clave, causa, motivo, capital, interés y mora condonados, y el folio del pago de $0 | `CreditoController.php:79`. Quedan también el pago de $0 y el cambio de estatus |
| Condonación formal (parcial o total) | Credito · updated | `condonacion_formal`: ∅ → Parcial_Capital | Clave, capital, intereses, mora, total, motivo y acuerdo | `CondonacionFormalController.php:86`. La total deja además el cambio de estatus |
| Reestructuración | Credito · updated | `reestructuracion`: «12 cuotas pendientes al 7.00 % (Moroso)» → «18 cuotas de $1,234.56 al 5 %» | Clave, motivo, saldo reestructurado, mora condonada, plazo, tasa y resolutivo | `ReestructuracionController.php:139`. El estatus (Moroso → Activo) va en otra fila |
| Desembolso | Desembolso · created | `monto_desembolsado`: ∅ → 24000.00 | Clave, monto y forma de desembolso | `DesembolsoController.php:78` |
| Alta de crédito | Credito · created | `monto_otorgado`: ∅ → 24000.00 | Clave, monto, plazo y las dos tasas | `CreditoObserver.php:29` |
| Edición de monto, plazo, tasas, fechas, modalidad, acreditado o claves | Credito · updated, una fila por campo | El campo que cambió, de su valor anterior al nuevo | Clave (la de antes, si lo que cambió fue la clave) y el cambio | `CreditoObserver.php:52`. No hay pantalla que edite estos datos; el observer registra el cambio venga de donde venga |
| Eliminar acreditado | Credito · deleted (una por crédito) y Acreditado · deleted | `monto_otorgado`: 24000.00 → ∅ / `nombre_completo`: nombre → ∅ | Clave, monto, estatus y los pagos activos que se borran en cascada | `AcreditadoController.php:337` y `:350`. Se registra **antes** de borrar y en la misma transacción |
| Aprobar / rechazar comprobación de uso | ComprobacionUso · updated | `estatus`: En_Revision → Aprobada o Rechazada | Clave y monto comprobado, o el motivo del rechazo | `ComprobacionController.php:115` y `:150`, en una transacción |

**Los campos del crédito que vigila el observer** (`CreditoObserver::CAMPOS`):
- `estatus`, `monto_otorgado`, `plazo_meses`;
- `tasa_interes_ordinario`, `tasa_interes_moratorio`;
- `fecha_entrega`, `fecha_contrato`;
- `modalidad_id`, `acreditado_id`;
- `clave_contrato`, `numero_contrato_oficial`, `folio_solicitud`.

---

## 2. Revisiones puntuales

### 2.1 `accion` es un enum (corregido)

La migración define `accion` como `enum('created','updated','deleted')`. `ComprobacionController` registraba `'aprobada'` y `'rechazada'`. En MySQL 8.0.41 con modo estricto (`'strict' => true`, el valor por defecto de Laravel), el insert fallaba:

```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'accion' at row 1
```

**Qué pasaba al aprobar o rechazar:**
- El operativo veía un error 500.
- La comprobación sí quedaba aprobada o rechazada.
- No quedaba fila en la bitácora.
- El ciudadano no recibía el aviso.
- Sin modo estricto, la acción se guardaba como texto vacío.

**Cómo quedó:**
- **Comprobaciones:** se registran como `updated` con el campo `estatus`, sin migración. La comprobación, su registro y el aviso van en una sola transacción.
- **Validación de la acción:** `AuditoriaLog::registrar` rechaza cualquier acción que no sea `created`, `updated` o `deleted`, con un mensaje claro, antes de llegar a la base.

**Para saber qué pasó en producción,** Diego puede contar las filas con la acción vacía:

```sql
SELECT COUNT(*) FROM auditoria_log WHERE accion = '';
```

Si da 0 y el servidor usa modo estricto, cada aprobación o rechazo anterior a este cambio dio error 500.

### 2.2 Campos del crédito y plan de pagos

- **Campos del crédito:** el observer vigilaba solo 4 campos; ahora vigila los 12 de la sección 1.
- **Plan de pagos** (tabla `amortizaciones`):
  - Los cambios de reducir plazo o cuota, condonaciones y reestructuración quedan en la fila de cada operación.
  - **No** hay una fila por cada cuota modificada. Las condonaciones y la reestructuración cambian las cuotas con updates masivos (`CondonacionFormalController.php:76`, `ReestructuracionController.php:100`) que no pasan por ningún observer.

### 2.3 Pagos que no pasan por el observer

No hay una API para la app móvil: no existe `routes/api.php` en `develop` ni en otra rama del repositorio, y Sanctum está instalado sin rutas.

Los pagos se crean solo en `PagoController.php:289` y `CreditoController.php:57`, y se cancelan solo en `PagoController.php:634`. Los tres usan Eloquent, así que el observer se ejecuta.

**Riesgo a futuro:** un proceso que escriba pagos con `DB::table(...)` o con un update masivo no quedaría en la bitácora.

---

## 3. La bitácora no se edita ni se borra

**Rutas, controladores y comandos.** Ninguno modifica ni elimina filas de `auditoria_log`; se revisó `app/`, `routes/`, `database/seeders/` y `deploy.sh`. Solo hay lecturas: `AuditoriaController` y `Credito::auditoria()`.

**Protección en el modelo.** `AuditoriaLog` lanza una excepción si alguien intenta `update()` o `delete()` sobre una fila.

**Límites que quedan:**
- **Updates masivos y SQL directo.** Un `AuditoriaLog::query()->update(...)` o una consulta en la base no pasan por el modelo. Impedirlo del todo requiere permisos en MySQL: que el usuario de la aplicación no tenga `UPDATE` ni `DELETE` sobre esa tabla. Eso es del servidor y lo decide Diego.
- **Borrar un usuario** (`UserController::destroy`) deja en `null` el `usuario_id` de sus filas, porque la llave foránea es `nullOnDelete`. El nombre se conserva en `usuario_nombre`.
- **El `down()` de la migración** borra la tabla. Solo corre si alguien revierte esa migración a mano.

---

## 4. Pantalla `/auditoria`

La página (`resources/js/Pages/Auditoria/Index.vue`) se reescribió con los nombres que manda `AuditoriaController`.

**Columnas:** fecha y hora, usuario, acción, entidad e id, campo con antes → después, descripción e IP. «Ver» abre el registro completo.

**Filtros:**
- **Entidad:** una lista con las entidades que hay en la bitácora.
- **Acción:** creado, actualizado o eliminado.
- **Usuario:** una lista con quienes aparecen en la bitácora, no todos los usuarios del sistema.
- **Fechas:** desde y hasta, en hora de Mérida.
- **Búsqueda:** clave de contrato, folio, usuario, IP o cualquier texto de la fila.

**Búsqueda por clave.** Si el texto coincide con una clave de contrato o un número de contrato oficial, también trae las filas de los pagos, desembolsos y comprobaciones de ese crédito. Eso incluye los registros viejos que dicen «Crédito ID: N», sin reescribirlos.

**Hora de Mérida.** `created_at` se guarda en UTC (`'timezone' => 'UTC'`). La pantalla y los filtros lo convierten: un pago de las 18:30 en Mérida se ve a las 18:30, no a las 00:30 del día siguiente.

**Totales.** Las tarjetas cuentan el total, las creaciones, las modificaciones y las eliminaciones con los filtros aplicados.

**Acceso.** Solo el Administrador puede entrar. Lo corrige la J.16 (PR #80) con `role:Administrador`; esta tarea no lo cambia.

---

## 5. «Desde dónde»: la IP

`AuditoriaLog::registrar` guarda `request()->ip()`, y `bootstrap/app.php` confía en cualquier proxy (`trustProxies(at: '*')`). Si el servidor no está detrás de un proxy que reescriba `X-Forwarded-For`, quien hace la petición puede mandar ese encabezado y elegir la IP que queda registrada. En la prueba del diagnóstico, un pago enviado con `X-Forwarded-For: 203.0.113.99` quedó con esa IP.

**No se cambia en esta tarea:** afecta a toda la aplicación y depende de cómo está montado el servidor. Diego tiene que confirmar si Hostinger pone un proxy delante y, si es así, limitar `trustProxies` a ese proxy.

---

## 6. Qué no cubre

- **Lecturas.** Están fuera de alcance. Las descargas del contrato y de la constancia se registran como `updated` (`CreditoController.php:178` y `:233`) desde antes y se dejan así.
- **Cada cuota del plan por separado.** Ver 2.2.
- **Cambios hechos fuera de Eloquent.** Ver 2.3 y 3.
- **La IP cuando el encabezado es falso.** Ver sección 5.
- **Registros viejos.** No se reescriben: los anteriores a este cambio conservan su formato (por ejemplo, «Pago REC-… — Crédito ID: 1», sin desglose ni valor nuevo).
- **Borrar un acreditado con créditos.** Se registra, pero no se bloquea. Bloquearlo cambia cómo funciona el sistema y lo decide Diego.

**Observaciones fuera de alcance** (no se cambian):
- **Condonación formal parcial.** En `develop` solo crea el registro y no toca el saldo. El PR #75 la aplica al plan.
- **Reestructuración.** No actualiza `plazo_meses` ni `tasa_interes_ordinario` del crédito: la nueva tasa queda en `reestructuraciones` y en las cuotas nuevas.
- **Cuota que vence hoy.** Cuenta como vencida al recalcular el estatus después de un pago (`fecha_vencimiento < now()`).

---

## 7. Del diagnóstico a la corrección

| Hallazgo (13/10) | Cómo quedó |
|---|---|
| Aprobar o rechazar una comprobación daba error 500 (enum) | `updated` + `estatus`, en una transacción |
| `/auditoria` solo mostraba la IP; solo funcionaba el filtro de acción | La página usa los nombres del controlador; todos los filtros funcionan |
| La reestructuración no se podía ejecutar (faltaba `$table`) | Ya lo corrigió en `develop` la rama `docs/crea-reestructuracion-anatocismo` |
| La reestructuración registraba Activo → Activo | Plan anterior → plan nuevo, con saldo, mora condonada y resolutivo |
| Eliminar un acreditado no dejaba registro | Una fila por crédito y una por el acreditado, antes de borrar |
| La condonación total no tenía fila propia | Fila `saldo_condonado` con causa, motivo y montos |
| Reducir plazo o cuota no dejaba rastro del plan | Fila `plan_de_pagos` con el antes y el después |
| El observer del crédito vigilaba 4 campos | Vigila 12 |
| Los pagos decían «Crédito ID: N» | Dicen la clave, el desglose y la forma de pago; la búsqueda por clave encuentra también los viejos |
| La hora se veía en UTC | Hora de Mérida en la pantalla y en los filtros |
| Nada impedía editar o borrar la bitácora desde el código | El modelo lo rechaza (con los límites de la sección 3) |

---

## 8. Pruebas

```bash
php artisan test tests/Feature/AuditoriaBitacoraTest.php
```

**Una prueba por operación de la sección 1.** Cada una la ejecuta como lo haría un operativo, por la ruta del sistema, y comprueba que existe la fila con:
- usuario, nombre e IP;
- campo, valor anterior y valor nuevo;
- clave y montos en la descripción.

**Además:**
- la bitácora no se puede editar ni borrar desde el modelo, y rechaza acciones fuera del enum;
- `/auditoria` filtra por entidad, acción, usuario y fechas en hora de Mérida;
- la búsqueda por clave encuentra los pagos y desembolsos de ese crédito aunque su descripción no tenga la clave.

En SQLite, el estado de la cuota es un `CHECK` sin «Reestructurada»; en MySQL lo agrega una migración. Por eso la prueba de reestructuración amplía la columna antes de empezar.
