# Tareas programadas, cola y correo

CREA depende de procesos que corren solos: el cálculo diario de la mora, los recordatorios de pago, el respaldo de la base y el envío de correos. Ninguno corre si en el servidor no existe el cron que dispara el programador (scheduler) de Laravel. Esta guía explica qué hay que configurar en Hostinger y qué pasa cuando algo falla.

**Todo se activa con una sola entrada de cron.** El mismo cron corre las tareas diarias y también envía los correos que están en la cola; no hace falta un segundo cron ni un proceso permanente.

---

## 1. Activar en Hostinger

### Paso previo: el `.env` del servidor

Antes de crear el cron, el `.env` del servidor debe tener estas variables (los valores entre `< >` son de ejemplo; los reales los tiene el administrador del hosting):

```
APP_URL=https://crea.iyemyucatan.com

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=<buzón de correo, por ejemplo crea@dominio>
MAIL_PASSWORD=<contraseña del buzón>
MAIL_FROM_ADDRESS=<el mismo buzón de MAIL_USERNAME>
MAIL_FROM_NAME="CREA IYEM Yucatán"
```

| Variable | Por qué importa |
|---|---|
| `APP_URL` | Los correos se arman desde el cron, sin un navegador de por medio, así que los enlaces salen de aquí. Si se queda en `http://localhost`, los enlaces de los correos no abren. |
| `QUEUE_CONNECTION=database` | Los recordatorios se guardan en la tabla `jobs` y el cron los envía. Con `sync` se mandarían en el momento del clic, sin reintentos. |
| `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT` | Servidor de salida de Hostinger: `smtp.hostinger.com`, puerto 465 con SSL (`smtps`). Si el 465 falla, se puede usar el 587 con `MAIL_SCHEME=null`. |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | El buzón completo y su contraseña (hPanel → Correos → Buzones). |
| `MAIL_FROM_ADDRESS` | Debe ser el mismo buzón de `MAIL_USERNAME`: los servidores de correo suelen rechazar un remitente distinto al usuario con el que se inició sesión. |

Después de cambiar el `.env` hay que correr `php artisan config:cache` (el deploy ya lo hace).

### El paso: crear el cron

En hPanel ve a **Avanzado → Cron Jobs** y crea uno nuevo:

- **Frecuencia:** cada minuto (`* * * * *`).
- **Comando:**

```
cd /home/USUARIO/domains/crea.iyemyucatan.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Cambia `USUARIO` por el usuario de la cuenta de hosting. Si el cron no encuentra `php`, usa la ruta completa del PHP que usa el sitio (por SSH: `which php` y `php -v`).

Con eso queda activo todo lo de la sección 2.

### Cómo comprobar que quedó

- **Al día siguiente**, en el panel de **Tareas programadas** (menú lateral → Sistema, solo administradores) deben aparecer en verde las ejecuciones de las 3:00, 7:00, 8:00 y 9:00. Una tarea diaria que lleva más de 24 horas sin correr sale en rojo.
- **Ese mismo día**, por SSH, `php artisan schedule:list` muestra todas las tareas y cuándo les toca. Eso confirma que están registradas, pero no que el cron esté corriendo; eso solo lo confirma el panel.

---

## 2. Qué depende del programador y de la cola

Todas las horas son de Mérida (`America/Merida`) y están en `routes/console.php`.

| Tarea | Qué hace | Cuándo | Qué pasa si no corre |
|---|---|---|---|
| `crea:respaldo-base` | Respaldo comprimido de la base (ver `docs/RESPALDO.md`). | Diario, 3:00 | No hay respaldo de ese día. Si pasan varios días, el último respaldo bueno se va quedando viejo. |
| `CalcularCarteraActiva` | Calcula el total de la cartera activa para la tarjeta del panel. | Diario, 7:00 | La tarjeta sigue mostrando el último cálculo con su fecha; si nunca corrió, dice que aún no se ha calculado. |
| `crea:update-moratorio` | Recalcula el interés moratorio de las cuotas vencidas. | Diario, 8:00 | La mora se queda congelada en el último cálculo: el portal del acreditado y los estados de cuenta muestran menos mora de la que corresponde. |
| `crea:recordatorios-pago` | Encola un correo para cada cuota que vence en 3 días. | Diario, 9:00 | Esas cuotas no reciben aviso. El proceso solo busca las que vencen justo en 3 días, así que un día sin correr deja a ese grupo sin aviso (se puede mandar desde la pantalla Recordatorios de pago). |
| `procesar-cola` | Envía los correos de la cola: los del proceso diario y los del botón de la pantalla Recordatorios de pago. Cada correo tiene 3 intentos. | Cada minuto, solo si hay correos pendientes | Los correos se quedan en la tabla `jobs` y nadie los recibe. Las cuotas quedan marcadas como avisadas, así que tampoco se vuelven a intentar. |
| Alertas de tareas fallidas | Avisan a los administradores en el ícono del encabezado cuando una tarea falla. | Cuando una tarea falla | Se generan dentro del programador; sin cron no hay alertas. Por eso el panel marca en rojo las tareas diarias que llevan más de 24 horas sin correr. |

Correos que **no** dependen del cron ni de la cola (se mandan en el momento, solo necesitan las variables `MAIL_*`):

| Correo | Para quién | Asunto |
|---|---|---|
| Verificación de correo | Ciudadano que se registra | Verifica tu correo electrónico — CREA |
| Recuperar contraseña | Usuario que lo pide | Recupera tu contraseña — CREA |
| Formulario de contacto | crea@iyemyucatan.com (responde al ciudadano) | [CREA] *asunto que escribió el ciudadano* |

El recordatorio de pago llega con el asunto *Recordatorio de pago CREA: tu cuota N vence el dd/mm/aaaa*. Todos salen con el remitente de `MAIL_FROM_NAME` y `MAIL_FROM_ADDRESS`.

---

## 3. La cola sin procesos permanentes

En un servidor propio, la cola se atiende con `php artisan queue:work` corriendo todo el tiempo, vigilado por un programa como Supervisor. El hosting compartido de Hostinger no permite procesos permanentes ni Supervisor, así que en CREA la cola la vacía el mismo programador:

- Cada minuto, si hay correos pendientes en la tabla `jobs`, la tarea `procesar-cola` corre `queue:work --stop-when-empty --max-time=50`: envía lo pendiente y termina antes del siguiente minuto.
- Si no hay pendientes, no corre ni se anota en la bitácora.
- Nunca corren dos a la vez (`withoutOverlapping`).

Si algún día CREA se muda a un VPS con Supervisor, se puede usar `queue:work` permanente; en ese caso hay que quitar `procesar-cola` de `routes/console.php` para que no haya dos procesos atendiendo la misma cola.

---

## 4. Qué pasa cuando algo falla

No hay un sistema de avisos aparte: todo pasa por la bitácora de tareas programadas, el panel y las alertas que ya existen.

**Una tarea programada falla** (termina con error o lanza una excepción):

1. Queda en la bitácora con estado error.
2. Sale en rojo en el panel de Tareas programadas.
3. Los administradores reciben una alerta en el ícono del encabezado.

**Un correo de recordatorio falla:**

1. Se reintenta: segundo intento al minuto y tercero a los 5 minutos.
2. Si los 3 fallan, el correo pasa a la tabla `failed_jobs` y el motivo queda en `storage/logs/laravel.log`.
3. Se quita la marca de "avisada" a la cuota, para poder mandarlo otra vez.
4. `procesar-cola` termina con error ("N correo(s) no se pudieron enviar después de 3 intentos"), y eso entra a la bitácora, al panel y a las alertas igual que cualquier tarea fallida.

Para reenviar, usa la pantalla **Recordatorios de pago** (menú lateral → Sistema): el botón toma las cuotas que ya no tienen la marca. No uses `php artisan queue:retry` para los recordatorios: el correo saldría sin volver a marcar la cuota, y después el botón podría mandarlo otra vez. Después puedes limpiar `failed_jobs` con `php artisan queue:flush`.

**El cron no está corriendo:**

- No hay ejecuciones, así que tampoco hay alertas.
- La única señal es el panel: las tareas diarias salen en rojo al pasar 24 horas sin correr. Conviene revisarlo de vez en cuando.

**Falla un correo directo** (verificación, contraseña o contacto):

- La persona ve un error en pantalla y el detalle queda en `storage/logs/laravel.log`.
- Estos correos no se reintentan solos.

---

## 5. Probar en local

En tu `.env` local:

```
MAIL_MAILER=log
QUEUE_CONNECTION=database
```

Con `MAIL_MAILER=log` ningún correo sale de tu computadora: se escriben completos en `storage/logs/laravel.log`.

1. Busca una cuota de un acreditado de prueba (con correo) y pon su vencimiento a 3 días.
2. `php artisan crea:recordatorios-pago` → debe decir `Recordatorios encolados: 1`.
3. `php artisan schedule:run` → corre `procesar-cola` y envía el correo.
4. Revisa el final de `storage/logs/laravel.log`: ahí está el correo con nombre, contrato, cuota, monto y fecha. Compáralos con el crédito en el sistema.

Si en lugar de `schedule:run` prefieres vaciar la cola directo: `php artisan queue:work --stop-when-empty`.
