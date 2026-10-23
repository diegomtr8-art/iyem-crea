# Procedimiento de despliegue

## 1. Requisitos del servidor

Para realizar el despliegue de la aplicación, el servidor debe contar con los siguientes componentes:

- **PHP:** versión 8.3, junto con las extensiones requeridas por Laravel y las dependencias definidas en `composer.json`.
- **Composer:** para la instalación de dependencias de PHP.
- **MySQL:** motor de base de datos utilizado por la aplicación.
- **Servidor web:** Apache o Nginx, configurado para servir la aplicación desde el directorio `public`.
- **SSH:** acceso habilitado para realizar la transferencia de archivos y ejecutar comandos remotos.
- **Permisos:** el usuario de despliegue debe contar con permisos adecuados sobre los directorios de la aplicación, especialmente `storage` y `bootstrap/cache`.
- **Respaldo automático:** el servidor debe permitir la ejecución de `php artisan crea:respaldo-base`, disponer de `mysqldump` y contar con un directorio privado para almacenar los respaldos.
- **Seguridad del directorio público:** únicamente el contenido de `public/` debe quedar expuesto a Internet. El proyecto Laravel debe almacenarse fuera de `public_html`, o utilizar una configuración equivalente que impida el acceso público a los archivos privados.

La versión de PHP, el acceso SSH, la ruta del DocumentRoot y demás configuraciones específicas del servidor están pendientes de verificación.

## 2. Variables de entorno de producción

La aplicación utiliza un archivo `.env` para almacenar la configuración del entorno. Este archivo debe existir en el servidor y no debe incluirse en el paquete de despliegue.

Las principales variables que deben configurarse son:

```dotenv
APP_NAME=
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# Respaldos de la base de datos
RESPALDO_DIRECTORIO=/ruta/privada/respaldos-crea
RESPALDO_MYSQLDUMP=mysqldump

MAIL_MAILER=
MAIL_SCHEME=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

Los valores anteriores son únicamente referencias de configuración y no contienen credenciales reales.

También deben configurarse las variables adicionales requeridas por los servicios que utiliza la aplicación, según el archivo `.env.example` del proyecto.

`RESPALDO_DIRECTORIO` debe apuntar a una carpeta privada, preferentemente fuera de `public_html`. La variable `RESPALDO_MYSQLDUMP` permite indicar la ruta del ejecutable cuando el servidor no encuentra `mysqldump` automáticamente.

Para conocer el funcionamiento, la conservación y la restauración de respaldos, consulta la guía [Respaldo de la base de datos](RESPALDO.md).

### 2.1. Configuración local del despliegue

El script `deploy.sh` utiliza un archivo `.env.deploy` ubicado en la raíz del proyecto para obtener los datos de conexión SSH y las rutas necesarias para el despliegue.

Para prepararlo, se debe copiar `.env.deploy.example` a `.env.deploy` y completar las siguientes variables:

| Variable | Descripción |
|---|---|
| `SSH_HOST` | Usuario y dirección del servidor SSH. |
| `SSH_PORT` | Puerto de conexión SSH. |
| `SSH_KEY` | Ruta local de la llave privada utilizada para autenticarse. |
| `REMOTE_DIR` | Directorio del proyecto Laravel en el servidor. |
| `PUBLIC_DIR` | Directorio público del sitio, cuya ubicación debe confirmar el responsable del servidor. |
| `SITE_URL` | URL del sitio utilizada para las comprobaciones de seguridad. |

El archivo `.env.deploy` está excluido mediante `.gitignore` y no debe incluirse en commits ni compartirse junto con el código. El archivo `.env.deploy.example` contiene únicamente valores de ejemplo.

Antes de ejecutar el despliegue, el responsable del servidor debe confirmar que las rutas corresponden a la estructura real de Hostinger y que únicamente los archivos públicos de Laravel pueden consultarse desde Internet.

La configuración de `PUBLIC_DIR` está pendiente de validación y, en la versión actual del script, esta variable se comprueba pero todavía no se utiliza para transferir archivos.

## 3. Procedimiento de despliegue

El despliegue se realiza mediante el script `deploy.sh`, siguiendo el orden descrito a continuación.

### 3.1. Validaciones previas

Antes de comenzar el despliegue, el script verifica que:

1. El repositorio Git no tenga modificaciones pendientes.
2. La rama actual sea `develop`, ya que el despliegue no debe ejecutarse desde ramas de desarrollo o corrección.
3. La rama local esté sincronizada con su correspondiente rama en `origin`.

Si alguna validación falla, el script detiene su ejecución.

Antes de iniciar el despliegue, el script comprueba que exista el archivo `.env.deploy` y que sus variables obligatorias estén configuradas. Asimismo, verifica que la URL `/.env` del sitio responda con HTTP 403 o 404 antes de realizar operaciones remotas. Si alguna validación falla, el despliegue se cancela.

### 3.2. Compilación del frontend

Se ejecuta `npm run build` para generar los archivos compilados de la interfaz desarrollada con Vue y Vite.

### 3.3. Respaldo de la base de datos

Antes de transferir archivos al servidor, el script ejecuta remotamente el comando `php artisan crea:respaldo-base`.

Este comando genera un respaldo comprimido de la base de datos MySQL/MariaDB, comprueba su integridad y elimina los respaldos de más de 30 días cuando el nuevo respaldo se ha generado correctamente.

Si el respaldo falla, el script detiene el despliegue y no continúa con la transferencia de archivos.

**Requisito previo:** el comando `crea:respaldo-base` debe estar disponible en el servidor antes de utilizar esta versión de `deploy.sh`. Su instalación inicial, así como la ubicación privada de los respaldos, deberán verificarse con el responsable del servidor.

Para conocer la configuración, ejecución manual y restauración de respaldos, consultar [Respaldo de la base de datos](RESPALDO.md).

### 3.4. Exportación y transferencia

El código versionado se exporta mediante `git archive`, tomando como referencia el commit actual (`HEAD`). Se incluyen únicamente los directorios y archivos necesarios para ejecutar la aplicación, excluyendo documentación, pruebas, archivos de desarrollo y configuraciones locales.

No se transfieren `node_modules`, `vendor`, `.env`, `.git`, `tests/`, `docs/` ni archivos de documentación interna como `CHANGELOG_AUDITORIA.md`. Se conservan `composer.json` y `composer.lock`, necesarios para instalar las dependencias en el servidor.

Los archivos generados en `public/build` se transfieren por separado mediante SSH.

**Importante:** antes de ejecutar el despliegue, el responsable del servidor debe comprobar que el proyecto Laravel esté ubicado fuera de `public_html` y que únicamente su directorio `public/` sea accesible desde Internet. Si la estructura del hosting no permite esta separación, deberán establecerse reglas restrictivas en `.htaccess` y comprobar que los archivos privados respondan con HTTP 403 o 404.

### 3.5. Instalación de dependencias

En el servidor se ejecuta Composer con las opciones de optimización y sin instalar dependencias de desarrollo.

### 3.6. Confirmación y migraciones

Después de transferir los archivos e instalar las dependencias, el script solicita una confirmación explícita antes de ejecutar `php artisan migrate --force`.

Para autorizar la operación, el responsable del despliegue debe escribir `SI`. Cualquier otra respuesta cancela la ejecución de las migraciones, aunque los archivos ya se hayan transferido al servidor.

Una vez autorizadas, se ejecutan las migraciones y se actualizan las cachés de configuración, rutas, vistas y eventos de Laravel. También se actualizan los archivos del build utilizados por la aplicación.

Los seeders no se ejecutan automáticamente durante cada despliegue, para evitar sobrescribir tasas, plazos, roles o permisos modificados desde la aplicación. Si se requieren durante una instalación inicial, deberán ejecutarse manualmente con autorización del responsable del servidor.

**Advertencia:** si se rechazan las migraciones o estas fallan, el servidor puede quedar con código actualizado y una base de datos que todavía no corresponde a esa versión. El script no revierte automáticamente los cambios; el responsable del servidor deberá evaluar la recuperación antes de reanudar las operaciones.

Si una migración falla durante su ejecución, se debe detener el procedimiento y notificar al responsable del servidor. Antes de intentar nuevamente el despliegue, es necesario revisar el mensaje de error, consultar el estado de las migraciones y verificar si se aplicaron cambios parciales en la base de datos.

No se debe ejecutar nuevamente la migración ni restaurar automáticamente el respaldo sin evaluar el estado de la información. El responsable del servidor determinará si corresponde corregir el problema, completar manualmente alguna operación o utilizar el respaldo previo siguiendo el procedimiento de recuperación descrito en esta guía y en `RESPALDO.md`.

## 4. Verificación posterior al despliegue

Una vez completado el despliegue, se deben realizar las siguientes comprobaciones funcionales:

- **Inicio de sesión:** verificar que un usuario autorizado pueda iniciar sesión y acceder a las funciones correspondientes a su rol.
- **Crédito:** realizar una operación de prueba con un crédito, verificando que la información se registre y consulte correctamente.
- **Pago de prueba cancelado:** iniciar un pago de prueba en un entorno controlado y cancelarlo, comprobando que no se registre como un pago completado ni genere movimientos financieros indebidos.

Estas verificaciones deben realizarse con datos de prueba autorizados y evitando afectar operaciones reales.

### Verificación de seguridad

El script realiza una comprobación HTTP de `/.env` antes de iniciar las operaciones remotas y nuevamente al finalizar el despliegue.

La respuesta debe ser **403 (Forbidden)** o **404 (Not Found)**. Cualquier otro código, o un error de conexión, provoca que el script termine con error.

Esta comprobación no sustituye la revisión de la estructura de directorios del servidor. Antes de autorizar un despliegue en producción, también debe verificarse que archivos y directorios privados, como `.git`, `vendor`, `storage` y `composer.json`, no sean accesibles públicamente.

Si la comprobación final falla, el script informa del error, pero no revierte automáticamente los archivos transferidos ni las migraciones ejecutadas. El responsable del servidor deberá evaluar las medidas de recuperación necesarias.

## 5. Procedimiento de reversión (rollback)

Si se detecta un problema después del despliegue, debe evaluarse la necesidad de regresar a la versión anterior de la aplicación.

El procedimiento general es:

1. Suspender temporalmente las operaciones que puedan modificar información.
2. Identificar el último commit estable de la aplicación.
3. Verificar la disponibilidad e integridad del respaldo de la base de datos generado antes de las migraciones.
4. Restaurar el código de la versión estable utilizando el procedimiento de despliegue autorizado.
5. Restaurar la base de datos desde el respaldo correspondiente, si es necesario.
6. Limpiar o reconstruir las cachés de Laravel.
7. Repetir las verificaciones funcionales de inicio de sesión, crédito y pago cancelado.

El script `deploy.sh` no incluye un mecanismo de reversión automática. La recuperación debe coordinarse con el responsable del servidor y puede requerir un procedimiento manual distinto al despliegue habitual.

La restauración de la base de datos puede ocasionar la pérdida de cambios realizados después del respaldo. Por ello, debe ejecutarse únicamente con autorización y después de evaluar sus consecuencias.

Para conocer el procedimiento de restauración y las precauciones necesarias al recuperar una base de datos, consultar [Respaldo de la base de datos](RESPALDO.md). La restauración sobre producción requiere autorización expresa del responsable del sistema.

## 6. Información pendiente de verificación

Antes de ejecutar el despliegue en el servidor de pruebas o en producción, el responsable del servidor debe confirmar:

- Que PHP 8.3 y las extensiones requeridas estén disponibles, así como Composer, `mysqldump` y el acceso SSH.
- Que el proyecto Laravel esté ubicado fuera de `public_html` y que únicamente el contenido de `public/` sea accesible desde Internet. Si no es posible, deberán establecerse protecciones equivalentes y comprobar su funcionamiento.
- Las rutas reales de `REMOTE_DIR` y `PUBLIC_DIR`, así como la configuración del directorio público del dominio.
- Que las variables de `.env.deploy` estén correctamente configuradas en el equipo autorizado para desplegar.
- Que el archivo `.env` del servidor tenga los valores correctos, con `APP_DEBUG=false`, y que los respaldos se almacenen en una ubicación privada.
- Que `php artisan crea:respaldo-base` esté disponible en el servidor antes de ejecutar el script actualizado.
- Que exista un procedimiento autorizado de recuperación si falla una migración o una transferencia de archivos.
- Que los archivos y directorios privados no puedan descargarse mediante HTTP.

**Importante:** la preparación y revisión del script no implican autorización para ejecutarlo. El despliegue deberá realizarlo el responsable del servidor después de revisar los cambios.
