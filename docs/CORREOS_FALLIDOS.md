# Correos fallidos — reintentar o descartar desde el panel

Ticket: J.12 · Rama: `feature/crea-admin-reenvio-correos`

Cuando un correo de la cola agota sus 3 intentos, Laravel lo guarda en `failed_jobs`.
Antes nadie lo veía sin entrar al servidor. Ahora el administrador lo ve en
**Sistema → Correos fallidos** (`/admin/correos-fallidos`), lo reintenta o lo descarta,
y cada acción queda en `auditoria_log` con su usuario.

---

## 1. Diagnóstico

### Dónde se guardan los fallos

| Qué | Dónde | Valor |
|---|---|---|
| Tabla | `database/migrations/0001_01_01_000002_create_jobs_table.php` | `failed_jobs` (se crea junto con `jobs` y `job_batches`) |
| Driver | `config/queue.php` → `failed.driver` | `database-uuids` (`QUEUE_FAILED_DRIVER`) |
| Conexión | `config/queue.php` → `failed.database` | la misma de la app (`DB_CONNECTION`) |
| Cola | `.env.example` | `QUEUE_CONNECTION=database` |

### Qué guarda cada fallo

| Columna | Contenido |
|---|---|
| `id`, `uuid` | Identificadores del fallo. La pantalla usa `id`. |
| `connection`, `queue` | `database` y `default`. |
| `payload` | JSON del job: `displayName` (la clase, p. ej. `App\Jobs\EnviarRecordatorioPago`), `maxTries` (3) y `data.command` (el job serializado). Para el recordatorio, `data.command` solo trae el **id de la cuota**; no trae el correo ni el monto. |
| `exception` | Texto completo del error con toda la traza. **Puede incluir el correo del destinatario** (p. ej. un rechazo SMTP `550 <correo>`). |
| `failed_at` | Fecha y hora del fallo, en UTC. |

### Qué correos pueden llegar ahí

| Correo | Cómo se envía | ¿Llega a `failed_jobs`? |
|---|---|---|
| `RecordatorioPagoMail` | Con el job `EnviarRecordatorioPago` (`tries = 3`), desde el botón de Recordatorios de pago. Con la J.10, también desde el proceso diario. | **Sí** |
| `MensajeCiudadanoMail` | `Mail::send` en el momento, al buzón de contacto (`config('contacto.correo')`) | No |
| `ContactoMensaje` | `Mail::send` en el momento, al buzón de contacto | No |

Hoy el único tipo que aparece en la pantalla es el recordatorio de pago. Si otro correo
empieza a salir por la cola, se agrega su job en `CorreosFallidosService::TIPOS`.

Cuando el job falla por tercera vez, `EnviarRecordatorioPago::failed()` borra
`recordatorio_enviado_at` de la cuota, para que el aviso se pueda volver a mandar.

---

## 2. La pantalla

- Solo entra el rol **Administrador** (`middleware('role:Administrador')`, igual que las otras rutas `admin/*`). Cualquier otro usuario recibe 403.
- Columnas: fecha del fallo (hora de Mérida), tipo de correo, destinatario enmascarado (`a***@dominio.com`), crédito y cuota, y el error resumido.
- **No se muestra el payload.** Del error se muestra solo la primera línea, sin la ruta del archivo y con los correos enmascarados.
- El destinatario es el correo **actual** del acreditado, porque el fallo no lo guarda (ver tabla de arriba).
- Si un fallo ya no aplica, se ve en la fila («Ya no aplica: la cuota ya está pagada») antes de reintentarlo.

Archivos:

| Archivo | Qué hace |
|---|---|
| `app/Services/CorreosFallidosService.php` | Lee `failed_jobs`, revisa si el correo aplica, reintenta, descarta y registra en la bitácora |
| `app/Http/Controllers/CorreosFallidosController.php` | Las cuatro acciones de la pantalla |
| `resources/js/Pages/Admin/CorreosFallidos.vue` | La pantalla |
| `routes/web.php` | 4 rutas `admin/correos-fallidos…` con `role:Administrador` |
| `resources/js/components/AppSidebar.vue` | Entrada «Correos fallidos» en el grupo Sistema |

---

## 3. Reintentar

Cada botón pide confirmación. **Reintentar todos** hace lo mismo con cada fallo, del más
viejo al más nuevo, y al final dice cuántos se reenviaron y por qué se descartaron los demás.

Antes de reenviar se revisa, en este orden:

1. Que la cuota siga existiendo.
2. Que no esté `Pagado`, `Condonado`, `Reestructurada` ni en `Gracia` (lo mismo que revisa el job).
3. Que el acreditado tenga correo (lo mismo que revisa el job).
4. Que la cuota no haya vencido. Se comparan fechas en hora de Mérida: si vence hoy, todavía se avisa.
5. Que la cuota no tenga `recordatorio_enviado_at`.

Si alguna falla, el fallo **se descarta sin enviar**: sale de la lista, se registra en la
bitácora y la pantalla dice el motivo.

Si todo está bien, se marca `recordatorio_enviado_at` y el job vuelve a la cola. Se usa
la misma reserva que el botón y el proceso diario (`UPDATE … WHERE recordatorio_enviado_at IS NULL`).

### Cómo se evita mandar el mismo recordatorio dos veces (punto 5 del ticket)

- **La cuota ya tiene `recordatorio_enviado_at`.** Alguien ya le volvió a avisar después
  del fallo (el botón o el proceso diario). El reintento se descarta y dice cuándo fue el
  aviso. Esto incluye el caso en que alguien la marca entre la revisión y el reenvío.
- **Dos clics al mismo tiempo**, o dos administradores. El fallo se borra de `failed_jobs`
  **antes** de reenviar, dentro de una transacción. Solo la primera petición lo logra; la
  segunda recibe «Ese correo ya no estaba en la lista».
- **El reintento vuelve a fallar.** El job vuelve a quitar la marca y aparece un fallo nuevo
  en la lista, como la primera vez.

> El reintento **solo vuelve a poner el correo en la cola**. Sale cuando la cola se procesa:
> en producción, con la tarea `procesar-cola` de la J.10, que corre con el cron.
> **El cron lo activa Diego cuando él lo autorice.** Mientras esté apagado, el correo espera en `jobs`.

---

## 4. Descartar

Para fallos que no tiene sentido reintentar. Pide confirmación, quita el fallo de la lista
y **no envía nada**. Si otro administrador ya lo había reintentado o descartado, avisa en
lugar de hacer algo.

---

## 5. Registro en la bitácora

Cada reintento y cada descarte agrega una fila en `auditoria_log`, con el usuario y la IP
que pone `AuditoriaLog::registrar`. Solo se usan las acciones que ya acepta el enum
(`updated` y `deleted`), así que no hace falta migración.

| Caso | `accion` | `valor_anterior` → `valor_nuevo` | `descripcion` (ejemplo) |
|---|---|---|---|
| Reintento enviado a la cola | `updated` | Fallido → Reenviado | El recordatorio de pago de la cuota 1 del crédito CREA-001 se volvió a poner en la cola de envío. |
| Reintento que ya no aplica | `deleted` | Fallido → Descartado | … se descartó al reintentar: la cuota ya está pagada. |
| Descarte | `deleted` | Fallido → Descartado | … se descartó sin reenviar. Error original: (error resumido) |

`entidad` es `CorreoFallido`, `entidad_id` es el `id` del fallo y `campo_modificado` es `estado`.
La descripción **no lleva el correo completo**.

---

## 6. Contador en Tareas programadas

`/admin/tareas-programadas` muestra «Correos fallidos pendientes: N» con un enlace a la
pantalla. Es solo el número: no manda avisos ni repite la alerta de tareas fallidas de la J.8.

---

## 7. Pruebas

`tests/Feature/CorreosFallidosTest.php` (17 pruebas). Ninguna envía correos: el fallo se
provoca con un servidor de correo falso que rechaza al destinatario, y lo demás usa `Mail::fake()`.
Los datos son ficticios.

| Prueba | Qué comprueba |
|---|---|
| La pantalla lista el fallo | Correo enmascarado, también dentro del error, y sin payload |
| Resumen del error (×3) | Quita la ruta del archivo en Windows (con espacios) y en Linux, aunque el mensaje diga «in» |
| Reintento exitoso | Se envía 1 correo, se marca la cuota y queda en la bitácora con el usuario |
| Reintento que ya no aplica (×3) | Cuota pagada, acreditado sin correo, cuota vencida: no se envía y se registra el motivo |
| Descarte | No se envía, sale de la lista y queda en la bitácora con el usuario |
| Sin rol Administrador (×4) | 403 en ver, reintentar, reintentar todos y descartar |
| Cuota ya avisada | El reintento se descarta: 1 solo correo en total |
| Dos clics en reintentar | 1 solo correo y 1 solo registro |
| Reintentar todos | Reenvía el que aplica y descarta el pagado, con su motivo |
| Contador | Tareas programadas muestra 0 y luego 1 |

```bash
php artisan test tests/Feature/CorreosFallidosTest.php
```

### Probar a mano en local

Deja `MAIL_MAILER=log` en el `.env` local: así los correos quedan en
`storage/logs/laravel.log` y no le llega nada a nadie. Para tener un fallo que ver en la pantalla:

1. En **Recordatorios de pago**, presiona el botón. Necesitas al menos una cuota de prueba que venza
   en los próximos días y cuyo acreditado de prueba tenga correo. El mensaje debe decir «Recordatorios encolados: 1» o más.
2. Corre la cola **una vez**, apuntando a un servidor de correo que no existe. El cambio aplica
   solo a esa corrida; el `.env` no se toca. Los 3 intentos se hacen seguidos en unos segundos.

   PowerShell:
   ```powershell
   $env:MAIL_MAILER='smtp'; $env:MAIL_HOST='127.0.0.1'; $env:MAIL_PORT='2599'; php artisan queue:work --stop-when-empty; Remove-Item Env:MAIL_MAILER, Env:MAIL_HOST, Env:MAIL_PORT
   ```
   Bash:
   ```bash
   MAIL_MAILER=smtp MAIL_HOST=127.0.0.1 MAIL_PORT=2599 php artisan queue:work --stop-when-empty
   ```
3. Abre **Correos fallidos**: aparece el fallo con el error «Connection could not be established…».
4. Al reintentar, procesa la cola normal (`php artisan queue:work --stop-when-empty`). Con
   `MAIL_MAILER=log`, el correo queda en `storage/logs/laravel.log`.

**No hagas esta prueba en producción.**
