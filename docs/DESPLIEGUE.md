# Procedimiento de despliegue

## 1. Requisitos del servidor

Para realizar el despliegue de la aplicación, el servidor debe contar con los siguientes componentes:

- **PHP:** versión compatible con los requisitos establecidos en `composer.json`, junto con las extensiones necesarias para Laravel.
- **Composer:** para la instalación de dependencias de PHP.
- **MySQL:** motor de base de datos utilizado por la aplicación.
- **mysqldump:** herramienta para generar respaldos de la base de datos antes de ejecutar migraciones.
- **Servidor web:** Apache o Nginx, configurado para servir la aplicación desde el directorio `public`.
- **SSH:** acceso habilitado para realizar la transferencia de archivos y ejecutar comandos remotos.
- **Permisos:** el usuario de despliegue debe contar con permisos adecuados sobre los directorios de la aplicación, especialmente `storage` y `bootstrap/cache`.

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

## 3. Procedimiento de despliegue

El despliegue se realiza mediante el script `deploy.sh`, siguiendo el orden descrito a continuación.

### 3.1. Validaciones previas

Antes de comenzar el despliegue, el script verifica que:

1. El repositorio Git no tenga modificaciones pendientes.
2. La rama actual sea `develop` o `main`.
3. La rama local esté sincronizada con su correspondiente rama en `origin`.

Si alguna validación falla, el script detiene su ejecución.

### 3.2. Compilación del frontend

Se ejecuta `npm run build` para generar los archivos compilados de la interfaz desarrollada con Vue y Vite.

### 3.3. Exportación y transferencia

El código versionado se exporta mediante `git archive`, tomando como referencia el commit actual (`HEAD`).

Se excluyen del despliegue los archivos no versionados, como `node_modules`, `vendor`, archivos `.env` y bases de datos locales, siempre que Git no los esté rastreando.

Los archivos generados en `public/build` se transfieren por separado al servidor mediante SSH.

### 3.4. Instalación de dependencias

En el servidor se ejecuta Composer con las opciones de optimización y sin instalar dependencias de desarrollo.

### 3.5. Respaldo de la base de datos

Antes de ejecutar migraciones, el script contempla generar un respaldo mediante `mysqldump`.

El nombre del archivo incorpora la fecha y hora de ejecución, siguiendo un formato similar a:

`backup_YYYYMMDD_HHMMSS.sql`

**Pendiente:** confirmar la configuración de autenticación de MySQL, el nombre de la base de datos y la ubicación privada para almacenar los respaldos.

El despliegue no debe continuar con las migraciones si el respaldo falla.

### 3.6. Confirmación y migraciones

El script solicita una confirmación explícita antes de ejecutar `php artisan migrate --force`.

Para autorizar la operación, el usuario debe escribir `SI`. Cualquier otra respuesta cancela la ejecución.

Después de las migraciones se ejecutan los seeders configurados en el script y se actualizan las cachés de Laravel.

## 4. Verificación posterior al despliegue

Una vez completado el despliegue, se deben realizar las siguientes comprobaciones funcionales:

- **Inicio de sesión:** verificar que un usuario autorizado pueda iniciar sesión y acceder a las funciones correspondientes a su rol.
- **Crédito:** realizar una operación de prueba con un crédito, verificando que la información se registre y consulte correctamente.
- **Pago de prueba cancelado:** iniciar un pago de prueba en un entorno controlado y cancelarlo, comprobando que no se registre como un pago completado ni genere movimientos financieros indebidos.

Estas verificaciones deben realizarse con datos de prueba autorizados y evitando afectar operaciones reales.

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

La restauración de la base de datos puede ocasionar la pérdida de cambios realizados después del respaldo. Por ello, debe ejecutarse únicamente con autorización y después de evaluar sus consecuencias.

**Pendiente:** confirmar el procedimiento específico de restauración, la ubicación de los respaldos y los permisos necesarios en el servidor.

## 6. Información pendiente de verificación

Antes de realizar un despliegue en producción, se requiere confirmar:

- Acceso SSH y permisos del usuario de despliegue.
- Versión de PHP y extensiones instaladas.
- Ruta del DocumentRoot y configuración del servidor web.
- Ruta de instalación de la aplicación.
- Disponibilidad de Composer y `mysqldump`.
- Configuración de acceso a MySQL para realizar respaldos.
- Directorio autorizado para almacenar respaldos.
- Procedimiento de restauración y recuperación ante fallos.
