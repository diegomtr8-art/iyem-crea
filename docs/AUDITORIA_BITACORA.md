# Bitácora de auditoría: diagnóstico

CREA guarda en la tabla `auditoria_log` quién hizo qué operación, cuándo y desde qué IP. Las filas se escriben con `AuditoriaLog::registrar(...)`, ya sea desde los observers de `Credito` y `Pago` o con llamadas directas en algunos controladores. Se consultan en la pantalla **Bitácora Auditoría** (`/auditoria`).

Este documento revisa, operación por operación, qué queda registrado hoy y qué falta.

**Cómo se revisó.** Sobre `develop` (commit `3a66703`, 13/10/2026) se ejecutó cada operación en una base de prueba y después se leyó lo que quedó en `auditoria_log`. El punto del `enum` se probó en MySQL 8.0 con el mismo modo estricto que usa Laravel. Los números de línea corresponden a ese commit.

---

## 1. Operaciones que mueven dinero o cambian un crédito

| Operación | ¿Deja registro? | Qué queda hoy | Dónde está la llamada | Qué falta |
|---|---|---|---|---|
| Registrar pago | Sí | Pago · created. Sin campo ni valores. *«Pago REC-000001-2026 registrado. Monto: $2076.61 — Crédito ID: 1»* | `PagoController::store` crea el pago en `PagoController.php:289` → `PagoObserver.php:12` | La clave del crédito (dice «Crédito ID: 1»), el desglose mora / interés / capital, la forma de pago y el monto como valor nuevo. |
| Cancelar pago | Sí | Pago · updated · `cancelado`: Activo → Cancelado. *«Pago REC-… cancelado. Motivo: …»* | `PagoController.php:634` → `PagoObserver.php:26` | La clave del crédito y el monto del pago cancelado. |
| Cambio de estatus del crédito | Sí | Credito · updated · `estatus`: Activo → Moroso. *«Modificación en crédito CREA-…»* | `CreditoObserver.php:29`. Lo disparan `PagoController.php:313-317` y `:647-651`, `CreditoController.php:75`, `CondonacionFormalController.php:79` y `ReestructuracionController.php:126` | La descripción no dice qué operación causó el cambio. |
| Reducir plazo / reducir cuota | Solo el pago | La misma fila que un pago normal. | `PagoController::store` → `aplicarAbonoCapital` (`PagoController.php:336`; reescribe cuotas en `:467`, `:484` y `:542`) | Que se eligió reducir plazo o cuota, cuánto se abonó a capital y cómo cambió el plan (cuotas pendientes y monto de la cuota, antes y después). `plazo_meses` no cambia, así que el observer no ve nada. |
| Condonación total (`CreditoController::condonar`) | Indirecto | 1) un pago de $0: *«Pago REC-… registrado. Monto: $0.00 — Crédito ID: 1»*; 2) `estatus`: Activo → Liquidado. | `CreditoController.php:57` (pago de $0) y `:75` (estatus) → observers | No hay fila de condonación: la causa, el motivo y los montos condonados (capital, interés y mora) solo están en las observaciones del pago de $0. |
| Condonación formal parcial | Sí | Credito · updated · `condonacion_formal`: ∅ → Parcial_Mora. *«Condonación formal registrada. Tipo: Parcial_Mora. Monto total: $350.25. Acuerdo: AC-01»* | `CondonacionFormalController.php:86` | La clave del crédito, el desglose por concepto y el motivo. |
| Condonación formal total | Sí | La misma fila (∅ → Total) más `estatus`: Activo → Liquidado. | `CondonacionFormalController.php:86`; cuotas en `:78` (sin registro por cuota) | Lo mismo que la parcial. |
| Reestructuración | **No se puede ejecutar** | Error 500: *«no such table: reestructuracions»*. | `ReestructuracionController.php:72` | Ver 1.1. |
| Desembolso | Sí | Desembolso · created. Sin campo ni valores. *«Desembolso registrado para crédito CREA-…. Monto: $24000.00 vía Transferencia»* | `DesembolsoController.php:78` | El monto como valor nuevo (hoy solo está en el texto). Fuera de eso, está completo. |
| Alta de crédito | Sí | Credito · created. *«Crédito CREA-… creado. Monto: $…»* | `CreditoObserver.php:12`. Créditos creados en `AcreditadoController.php:150` y `SolicitudOperativoController.php:447` | Plazo y tasas. |
| Edición de monto, plazo o tasa | Parcial | No existe pantalla ni ruta que edite estos datos después de crear el crédito. Si cambian por otra vía, el observer registra `monto_otorgado`, `plazo_meses` y `tasa_interes_ordinario` (probado: 24000.00 → 25000.00). | `CreditoObserver.php:23-40` | No registra `tasa_interes_moratorio` ni `fecha_entrega`: se cambiaron en la prueba y no quedó ninguna fila. Ver 2.2. |
| Eliminar acreditado | **No** | Nada. | `AcreditadoController::destroy` (`AcreditadoController.php:324`) | Borra en cascada los créditos, cuotas, pagos, desembolso, condonaciones y reestructuraciones del acreditado. En la prueba quedaron 0 créditos, 0 pagos y 0 filas nuevas en la bitácora. No hay botón en pantalla, pero la ruta `DELETE /acreditados/{id}` está abierta a cualquier usuario operativo. |
| Aprobar / rechazar comprobación de uso | **Falla** | Error 500 en MySQL. | `ComprobacionController.php:110` y `:140` | Ver 2.1. |

### 1.1 Reestructuración

- **No funciona en `develop`.** Al modelo `Reestructuracion` le falta `protected $table = 'reestructuraciones';`, así que Laravel busca la tabla `reestructuracions`. La rama `docs/crea-reestructuracion-anatocismo` ya lo corrige, pero todavía no está fusionada.
- **Valor anterior equivocado.** Con el modelo corregido, la fila de la reestructuración queda con *«reestructuracion: Activo → Activo»*. El valor anterior se lee en `ReestructuracionController.php:133`, cuando el crédito ya se cambió a Activo en la línea 126. El cambio real de estatus (Moroso → Activo) sí lo registra el observer en otra fila.
- **Falta información.** La descripción tampoco trae la clave del crédito, el saldo reestructurado, la mora condonada ni la nueva tasa.

---

## 2. Revisiones puntuales

### 2.1 `accion` es un enum, pero dos controladores mandan otros valores

La migración `2026_05_07_000001_create_auditoria_log_table.php` define `accion` como `enum('created','updated','deleted')`. `ComprobacionController` registra `'aprobada'` (línea 110) y `'rechazada'` (línea 140).

**Resultado en MySQL 8.0.41.** Laravel usa modo estricto por defecto (`'strict' => true` en `config/database.php`, tanto para `mysql` como para `mariadb`), y el insert falla con este error:

```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'accion' at row 1
```

Al aprobar una comprobación pasa esto:

1. El operativo ve un **error 500**.
2. La comprobación **sí queda como Aprobada**: se actualiza en la línea 102, antes del registro, y no hay transacción.
3. **No queda ninguna fila** en la bitácora.
4. **El ciudadano no recibe el aviso**: el `AnuncioCiudadano` de la línea 115 nunca se crea.

Rechazar se comporta igual.

**Sin modo estricto**, el valor se guarda como texto vacío (`''`) y se pierde la acción. En las pruebas con SQLite, el enum es una restricción CHECK y también falla.

Para saber qué pasó en producción, Diego puede revisar si hay filas con `accion = ''`:

```sql
SELECT COUNT(*) FROM auditoria_log WHERE accion = '';
```

Si el resultado es 0 y el servidor usa modo estricto, cada aprobación o rechazo de comprobación dio error 500.

### 2.2 Campos del crédito que el observer no vigila

`CreditoObserver::updated` solo revisa `estatus`, `monto_otorgado`, `plazo_meses` y `tasa_interes_ordinario`. Estos campos también afectan dinero, estado o identidad del crédito y no se registran:

| Campo | Por qué importa |
|---|---|
| `tasa_interes_moratorio` | Define la mora que se cobra. |
| `fecha_entrega` | De ella salen las fechas de vencimiento y, por lo tanto, la mora. También cuenta para el presupuesto del año. |
| `modalidad_id` | Programa del crédito: tasas y presupuesto. |
| `acreditado_id` | A quién se le cobra. |
| `clave_contrato`, `numero_contrato_oficial`, `folio_solicitud` | Identifican el crédito en contratos, recibos y búsquedas. |
| `fecha_contrato` | Fecha legal del contrato. |

Tampoco se registra el **plan de pagos** (la tabla `amortizaciones`). No es un campo del crédito, pero cambia con reducir plazo o cuota, la reestructuración y las condonaciones. Las condonaciones y la reestructuración lo hacen con updates masivos (`CondonacionFormalController.php:78`, `ReestructuracionController.php:90`), que no pasan por ningún observer.

### 2.3 Pagos que no pasan por el observer

No hay una API para la app móvil: no existe `routes/api.php` en `develop` ni en ninguna otra rama del repositorio. Sanctum está instalado, pero sin rutas.

Los pagos se crean solo en dos lugares, y los dos usan Eloquent, así que el observer sí se ejecuta:

- `PagoController.php:289`
- `CreditoController.php:57`

Los pagos se cancelan solo en `PagoController.php:634`, también con Eloquent. No hay updates masivos sobre `pagos`.

**Riesgo a futuro:** si se agrega una API o un proceso que escriba pagos con `DB::table(...)` o con un update masivo, ese pago no quedaría en la bitácora.

---

## 3. La bitácora no se edita ni se borra

No existe ninguna ruta, controlador, comando, job ni seeder que modifique o elimine filas de `auditoria_log`; se revisó todo `app/`, `routes/`, `database/seeders/` y `deploy.sh`. Solo hay lecturas (`AuditoriaController`, la relación `Credito::auditoria()`). El único `drop` es el `down()` de la propia migración, que solo corre si alguien revierte esa migración a mano.

Hay dos efectos indirectos:

- **Borrar un usuario** (`UserController::destroy`) pone en `null` el `usuario_id` de sus filas, porque la llave foránea es `nullOnDelete`. El nombre se conserva en `usuario_nombre`.
- **Nada lo impide a nivel de código.** El modelo `AuditoriaLog` acepta `update()` y `delete()` como cualquier otro. Hoy nadie los usa, pero nada evitaría que alguien lo haga en el futuro.

---

## 4. Pantalla `/auditoria`

**La página y el controlador no coinciden.** `resources/js/Pages/Auditoria/Index.vue` se escribió para otro formato de datos (`event`, `auditable_type`, `user_name`, `created_at`...). `AuditoriaController` manda `accion`, `entidad`, `usuario`, `fecha`...

| | La página manda / espera | El controlador recibe / envía |
|---|---|---|
| Búsqueda | `search` | `buscar` |
| Entidad | `modelo` | `entidad` |
| Fecha | `fecha` (un solo día) | `fecha_desde` y `fecha_hasta` |
| Usuario | no tiene control | `usuario_id` |
| Columnas de la tabla | `created_at`, `user_name`, `event`, `auditable_type`, `description` | `fecha`, `usuario`, `accion`, `entidad`, `descripcion` |
| Totales | `totales` | no se envía |

Esto tiene cuatro consecuencias:

1. **Filtros:** de los filtros, **solo funciona el de acción**. La búsqueda, la entidad y la fecha se ignoran, y no hay filtro por usuario.
2. **Tabla:** cada fila muestra la fecha «—», el usuario «Sistema», la acción «—», el módulo «—» y la descripción «—». **Solo se ve la IP.**
3. **Totales:** las tarjetas de totales no aparecen.
4. **Acciones:** el selector ofrece «Inicio de sesión» y «Cierre de sesión», que nunca se registran.

**Otros problemas de la pantalla:**

- **Hora en UTC.** La aplicación usa `'timezone' => 'UTC'`, así que un pago de las 18:30 en Mérida aparece como 00:30 del día siguiente. El filtro por fechas también usa días UTC.
- **Búsqueda por clave de contrato.** La búsqueda solo revisa la descripción y los valores. Los pagos dicen «Crédito ID: N», no la clave, así que buscar `CREA-2026-…` no encuentra los pagos de ese crédito.
- **Acceso.** El menú solo muestra la bitácora a quien tiene el permiso `ver.usuarios`, pero la ruta no lo verifica: cualquier usuario operativo puede abrir `/auditoria` escribiendo la dirección.

---

## 5. «Desde dónde»: la IP

`AuditoriaLog::registrar` guarda `request()->ip()`, y `bootstrap/app.php` confía en cualquier proxy (`trustProxies(at: '*')`). Si el servidor no está detrás de un proxy que reescriba el encabezado `X-Forwarded-For`, el usuario puede mandar ese encabezado y elegir la IP que queda registrada.

En la prueba, un pago enviado con `X-Forwarded-For: 203.0.113.99` quedó con esa IP.

No se cambia en esta tarea, porque afecta a toda la aplicación y depende de cómo está montado el servidor. Diego tendría que confirmar si Hostinger pone un proxy delante y, si es así, limitar `trustProxies` a ese proxy.

---

## 6. Observaciones fuera de alcance

- **Descargas registradas como cambios.** Las descargas del contrato y de la constancia se registran como `updated` (`CreditoController.php:162` y `:217`), aunque son lecturas. Se dejan como están.
- **La condonación formal parcial no toca el saldo.** Solo crea el registro; no aplica los montos a las cuotas, así que el saldo no cambia.
- **La reestructuración no actualiza el crédito.** No cambia `plazo_meses` ni `tasa_interes_ordinario`; la nueva tasa solo queda en `reestructuraciones` y en las cuotas nuevas.
- **Una cuota que vence hoy cuenta como vencida.** Al recalcular el estatus después de un pago o una cancelación, se compara `fecha_vencimiento < now()`. Por eso, una cuota que vence hoy hace que el crédito pase a Moroso.

---

## 7. Propuesta de corrección (pendiente de aprobación)

Todo con el patrón que ya existe (`AuditoriaLog::registrar` y los observers), sin crear otro sistema y sin cambiar cálculos de pagos ni de mora:

1. **Comprobaciones.**
   - Registrar `updated` con campo `estatus` (por ejemplo, En_Revision → Aprobada) y el monto comprobado, en lugar de `aprobada` o `rechazada`.
   - Guardar la comprobación y su registro en una sola transacción.
   - No hace falta migración.
2. **Pagos.**
   - El observer agrega la clave del crédito, el desglose, la forma de pago y el monto como valor nuevo.
   - Cuando el pago reduce plazo o cuota, se agrega una fila con el plan antes y después. Se calcula con el *snapshot* que el pago ya guarda, sin tocar `PagoController`.
3. **Crédito.** El observer vigila también los campos de la tabla 2.2 y dice en la descripción qué cambió y de cuánto a cuánto.
4. **Condonaciones.**
   - Una fila propia para la condonación total, con la causa, el motivo y los montos condonados.
   - Clave, desglose y motivo en la condonación formal.
5. **Reestructuración.**
   - Valor anterior correcto.
   - Clave, saldo, mora condonada, nuevo plazo y nueva tasa.
   - La línea `$table` del modelo, igual a la de la otra rama, para que al fusionar ambas no haya conflicto.
6. **Eliminar acreditado.** Antes de borrar, una fila `deleted` por cada crédito, con clave, monto y pagos. Queda a decisión de Diego si además conviene impedir borrar acreditados con créditos.
7. **Pantalla `/auditoria`.**
   - Que la página y el controlador usen los mismos nombres.
   - Filtros por entidad, acción, usuario y rango de fechas en hora de Mérida.
   - Búsqueda por clave que también encuentre los pagos y desembolsos de ese crédito, incluidos los registros viejos, sin reescribirlos.
   - Mostrar campo, antes → después, usuario, IP y descripción.
8. **Protección.**
   - `AuditoriaLog` rechaza cualquier `update` o `delete` desde el código.
   - La ruta `/auditoria` pide el mismo permiso que el menú.
9. **Pruebas (Pest).** Una por operación de la tabla 1: después de ejecutarla, existe la fila con usuario, campo, valor anterior y valor nuevo correctos.

Fuera de esta tarea: la configuración de `trustProxies` (sección 5) y las observaciones de la sección 6.
