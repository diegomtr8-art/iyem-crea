# Respaldo de la base de datos

CREA guarda pagos y saldos de dinero público. Esta guía explica cómo funciona el respaldo diario de la base, dónde quedan los archivos, cómo sacarlos del servidor y cómo restaurar uno paso a paso. Está pensada para alguien que no conoce el sistema.

> **Los respaldos tienen datos personales** (nombres, CURP, domicilios, teléfonos y pagos de los acreditados). Nunca se suben a GitHub ni a ClickUp, no se mandan por correo y no se guardan en carpetas que se sincronicen con una nube personal (OneDrive, Google Drive, Dropbox).

---

## 1. Qué hace

El comando `php artisan crea:respaldo-base`:

1. Saca una copia completa de la base (estructura y datos) con `mysqldump`.
2. Revisa que la copia esté completa: `mysqldump` escribe `-- Dump completed` al final solo cuando terminó bien.
3. La comprime en un archivo `.sql.gz` con la fecha y hora en el nombre, y comprueba que al descomprimirlo sale exactamente la misma copia.
4. Borra los respaldos de más de **30 días**. Solo borra cuando el respaldo nuevo salió bien, así que si el respaldo falla varios días seguidos no se pierden los anteriores.

Corre solo **todos los días a las 3:00 de la mañana, hora de Mérida** (está en `routes/console.php`). Para eso el servidor necesita el cron del scheduler de Laravel, el mismo que ya usan los recordatorios de pago:

```
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Para entrar a la base usa los mismos datos del `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). La contraseña no se pasa en la línea de comandos: se escribe en un archivo temporal que solo el dueño puede leer y que se borra al terminar.

### Cómo saber si funcionó

- **Panel de Tareas programadas** (menú lateral → Sistema → Tareas programadas, solo para administradores): `crea:respaldo-base` aparece con su última ejecución. Si no ha corrido en 24 horas o terminó con error, sale en rojo.
- **Ícono de tareas fallidas** del encabezado: si el respaldo falla, los administradores reciben una alerta.
- **Motivo exacto del error**: queda en `storage/logs/laravel.log`. Busca la línea `Falló el respaldo de la base de datos`.

Si se corre a mano, el resultado sale en la terminal, pero no queda en el panel. Ahí solo se registran las ejecuciones que lanza el scheduler.

---

## 2. Dónde queda el archivo

El nombre lleva la fecha y la hora de Mérida en que se generó:

```
crea-respaldo-2026-10-05_030000.sql.gz
```

La carpeta se define en el `.env`:

| Variable | Para qué | Si no se define |
|---|---|---|
| `RESPALDO_DIRECTORIO` | Carpeta donde se guardan los respaldos | `storage/app/private/respaldos` dentro del proyecto |
| `RESPALDO_MYSQLDUMP` | Ruta completa de `mysqldump`, si el cron no lo encuentra | `mysqldump` |

**En el servidor**, el proyecto está dentro de `public_html`. Por eso se recomienda una carpeta fuera de `public_html`, en el directorio personal de la cuenta. Por ejemplo:

```
RESPALDO_DIRECTORIO=/home/USUARIO_DEL_HOSTING/respaldos-crea
```

Después de cambiar el `.env` hay que correr `php artisan config:cache`, porque en producción la configuración está en caché.

Como protección extra, el comando deja en la carpeta un `.htaccess` que impide descargar los archivos desde el navegador. Esto sirve por si la carpeta queda dentro de la raíz web, pero no sustituye usar una carpeta fuera de `public_html`.

En una computadora local con Windows, usa una carpeta sin acentos ni espacios y fuera de OneDrive, por ejemplo `C:\respaldos-crea`.

---

## 3. Correrlo a mano

```bash
php artisan crea:respaldo-base
```

Si sale bien, muestra la ruta y el tamaño del archivo y cuántos respaldos viejos borró:

```
Respaldo generado: /home/.../respaldos-crea/crea-respaldo-2026-10-05_101500.sql.gz (412.3 KB). Respaldos de más de 30 días borrados: 0.
```

Si falla, muestra el motivo y termina con código de salida 1.

### Errores comunes

| Mensaje | Qué hacer |
|---|---|
| `"mysqldump" no se reconoce...` (Windows) o `mysqldump: not found` (Linux) | Buscar la ruta con `which mysqldump` y ponerla en `RESPALDO_MYSQLDUMP`. |
| `Access denied for user ...` | Revisar `DB_USERNAME` y `DB_PASSWORD` en el `.env`. |
| `Solo se respaldan bases MySQL/MariaDB` | El `.env` apunta a SQLite. El comando solo sirve para MySQL o MariaDB. |
| `The Process class relies on proc_open...` | El PHP del servidor tiene deshabilitado `proc_open`. Hay que pedir al hosting que lo habilite para PHP de línea de comandos. |

---

## 4. Copiarlo fuera del servidor

Un respaldo que solo vive en el servidor no sirve si se pierde el servidor. Al menos una vez por semana, copia el respaldo más reciente a un lugar seguro con acceso restringido, por ejemplo un disco cifrado o una carpeta institucional con permisos.

**Opción A, por SSH**, desde la computadora que ya tiene la llave del deploy. El usuario, el servidor y el puerto son los de `deploy.sh`:

```bash
scp -i ~/.ssh/id_deploy -P 65002 USUARIO@SERVIDOR:respaldos-crea/crea-respaldo-2026-10-05_030000.sql.gz .
```

**Opción B, sin terminal**: en hPanel ve a Archivos → Administrador de archivos, abre la carpeta de respaldos y descarga el archivo con clic derecho → Descargar.

Después de copiarlo, comprueba que el archivo descargado pesa lo mismo que en el servidor.

---

## 5. Restaurar un respaldo paso a paso

**Siempre restaura primero en una base nueva y vacía**, nunca directo sobre la base de producción. Así puedes revisar que el respaldo sirve sin poner nada en riesgo. Restaurar sobre producción es una decisión de Diego (ver el final de esta sección).

Para restaurar necesitas el programa `mysql` (cliente de línea de comandos), que viene con MySQL o MariaDB.

### Paso 1. Crear una base vacía

En el servidor: en hPanel ve a Bases de datos → Administración y crea una base nueva con su usuario.

En una computadora local:

```bash
mysql -u root -p -e "CREATE DATABASE crea_restaurada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Paso 2. Descomprimir el archivo

En Linux o en el servidor (`-k` conserva el `.gz`):

```bash
gunzip -k crea-respaldo-2026-10-05_030000.sql.gz
```

En Windows no viene `gunzip`, pero PHP puede descomprimirlo:

```powershell
php -r "copy('compress.zlib://C:/respaldos-crea/crea-respaldo-2026-10-05_030000.sql.gz', 'C:/respaldos-crea/restaurar.sql');"
```

### Paso 3. Cargarlo en la base vacía

En Linux o en el servidor:

```bash
mysql -u USUARIO -p --default-character-set=utf8mb4 crea_restaurada < crea-respaldo-2026-10-05_030000.sql
```

En Windows (PowerShell no acepta `<`, por eso se usa `cmd /c`):

```powershell
cmd /c "mysql -u root -p --default-character-set=utf8mb4 crea_restaurada < C:\respaldos-crea\restaurar.sql"
```

Pide la contraseña y no muestra nada si todo salió bien.

### Paso 4. Comprobar que la restauración quedó bien

Corre las consultas de conciliación (`docs/auditoria/sql/A2_conciliacion.sql`) en la base original y en la restaurada, y compara los resultados. Deben ser idénticos.

```bash
mysql -u USUARIO -p --default-character-set=utf8mb4 --table BASE_ORIGINAL < docs/auditoria/sql/A2_conciliacion.sql > a2_original.txt
mysql -u USUARIO -p --default-character-set=utf8mb4 --table crea_restaurada < docs/auditoria/sql/A2_conciliacion.sql > a2_restaurada.txt
diff a2_original.txt a2_restaurada.txt && echo "Iguales"
```

En Windows, usa `cmd /c "..."` para las dos primeras líneas y `fc.exe a2_original.txt a2_restaurada.txt` en lugar de `diff`.

Para una comprobación más fuerte, compara la suma de verificación de las tablas de dinero en las dos bases. Los números deben coincidir uno a uno:

```bash
mysql -u USUARIO -p -e "CHECKSUM TABLE creditos, pagos, amortizaciones, acreditados" BASE_ORIGINAL
mysql -u USUARIO -p -e "CHECKSUM TABLE creditos, pagos, amortizaciones, acreditados" crea_restaurada
```

### Paso 5. Borrar el archivo descomprimido

El `.sql` descomprimido tiene los mismos datos personales y no tiene ninguna protección. Bórralo en cuanto termines. Si fue una prueba, borra también la base `crea_restaurada`.

### Si hay que restaurar producción

Solo si Diego lo decide:

1. Pon el sistema en mantenimiento: `php artisan down`.
2. Saca un respaldo del estado actual con `php artisan crea:respaldo-base`, por si hay que regresar.
3. Carga el respaldo elegido sobre la base de producción (pasos 2 y 3, con el nombre de la base de producción). El respaldo reemplaza cada tabla que contiene.
4. Corre `php artisan migrate --force`, por si el respaldo es de una versión anterior del sistema.
5. Comprueba con las consultas del paso 4 y vuelve a abrir el sistema: `php artisan up`.

Todo lo que se registró después de la hora del respaldo (pagos, solicitudes, cambios) se pierde y hay que capturarlo de nuevo. Con un respaldo diario, eso puede ser hasta un día de trabajo.

---

## 6. Respaldos de Hostinger

Hostinger hace sus propios respaldos de archivos y bases, que se descargan desde hPanel → Archivos → Respaldos. La frecuencia (semanal o diaria) y cuánto tiempo los guarda dependen del plan contratado. Son un complemento: viven en la infraestructura de Hostinger y no se pueden comprobar con una restauración propia. Por eso CREA tiene su respaldo diario aparte.
