# Recuperación ante desastre

Pasos para dejar CREA funcionando en un servidor nuevo, partiendo de cero, si el servidor actual se pierde, se corrompe o lo comprometen. Está escrita para alguien que no conoce el proyecto: sigue los pasos en orden y no te saltes las comprobaciones.

Se probó de punta a punta en un simulacro con datos de prueba (sección 14). Los tropiezos de ese simulacro ya están corregidos en los pasos.

> **Antes de empezar:** la decisión de recuperar, qué respaldo usar y cuándo abrir el sistema son de Diego. Esta guía nunca se prueba en producción: los simulacros se hacen en local o en un servidor desechable, con datos de prueba.

**Resumen:** con los accesos y los respaldos a la mano, recuperar CREA en Hostinger toma **unas 2 horas** (sección 14). Lo que hoy pone en riesgo la recuperación no es el tiempo, sino lo que no está respaldado (sección 15).

---

## 0. Lo que necesitas tener a la mano

| Qué | Para qué | Quién lo tiene |
|---|---|---|
| Acceso a hPanel (Hostinger) de la cuenta nueva o recuperada | Crear el sitio, la base, el SSH, el SSL y el cron | Diego |
| Acceso a la zona DNS de `iyemyucatan.com` | Apuntar `crea` al servidor nuevo | Diego |
| El último respaldo `.sql.gz` **copiado fuera del servidor** | Recuperar la base (`docs/RESPALDO.md`, J.9) | Quien hace la copia fuera del servidor |
| La copia de `storage/app/private` | Recuperar documentos y comprobantes | Hoy no existe (sección 15) |
| Los valores del `.env` de producción | Contraseñas de la base y del correo, `APP_KEY`, Google | Diego |
| Una computadora con Git Bash, PHP 8.2 o superior, Composer 2 y Node 20 o superior | Hacer el build y subir el código con `deploy.sh` | Tú |
| La llave SSH del deploy (`~/.ssh/id_deploy`) o una nueva | Entrar al servidor | Quien hace los deploys |

**Si el servidor fue comprometido**, no reutilices nada que estuviera en él. Crea contraseñas nuevas para la base, el buzón de correo y hPanel, una llave SSH nueva y un `APP_KEY` nuevo (paso 5).

Anota la hora de inicio. Al terminar, anota la hora de fin y cuánto tardó cada paso: así sabremos si los tiempos de esta guía siguen siendo reales.

En los comandos, cambia lo que está en mayúsculas por los datos reales:

- `USUARIO`: el usuario de la cuenta de hosting, por ejemplo `u123456789`.
- `IP_DEL_SERVIDOR`: la IP del servidor nuevo.
- `NOMBRE_BD` y `USUARIO_BD`: la base y el usuario que crees en el paso 3.

---

## 1. Dominio y DNS

Hazlo primero, porque es lo único que depende de esperar a otros.

La zona DNS de `iyemyucatan.com` está en Hostinger (los nameservers son `*.dns-parking.com`). `crea` es un registro **A** con TTL de 1800 segundos: después de cambiarlo, la gente puede tardar hasta 30 minutos en llegar al servidor nuevo.

1. Consigue la IP del servidor nuevo en hPanel (aparece en los detalles del sitio o del plan).
2. En hPanel → Dominios → `iyemyucatan.com` → DNS / Nameservers, edita el registro A `crea` y pon la IP nueva.
3. **No toques los registros MX:** el correo del dominio está en Google, no en Hostinger.

Para comprobar desde tu computadora:

```bash
nslookup crea.iyemyucatan.com 8.8.8.8
```

Si se perdió la cuenta de Hostinger completa, también se perdió la zona DNS. En ese caso hay que cambiar los nameservers desde el registrador del dominio, y ese cambio puede tardar hasta 24 horas en verse en todos lados.

---

## 2. Sitio, PHP y SSH

### En Hostinger

1. hPanel → Sitios web → Agregar sitio web, con el dominio `crea.iyemyucatan.com`. La carpeta del sitio queda en `~/domains/crea.iyemyucatan.com/public_html`, la misma que usa `deploy.sh`.
2. Hostinger deja un archivo de bienvenida (`default.php`) en `public_html`. Bórralo desde Archivos → Administrador de archivos.
3. hPanel → Avanzado → Configuración de PHP: elige PHP **8.2 o superior** (hasta 8.5).
4. hPanel → Avanzado → Acceso SSH: actívalo y agrega la llave pública (`~/.ssh/id_deploy.pub`). Anota el usuario, la IP y el puerto (Hostinger usa el 65002).

### Comprobar por SSH

Desde Git Bash, entra al servidor y revisa PHP y sus extensiones:

```bash
ssh -i ~/.ssh/id_deploy -p 65002 USUARIO@IP_DEL_SERVIDOR
php -v
for e in ctype dom fileinfo filter gd hash iconv json libxml mbstring openssl pcre pdo_mysql session simplexml tokenizer xml xmlreader xmlwriter zip zlib; do php -m | grep -qix "$e" || echo "FALTA: $e"; done
php -r 'echo function_exists("proc_open") ? "proc_open OK\n" : "FALTA proc_open\n";'
composer --version
mysql --version
```

- **`php -v`** debe decir 8.2, 8.3, 8.4 u 8.5, y la misma versión que elegiste en hPanel. Si no coinciden, sigue «Si la terminal usa otra versión de PHP», abajo.
- **El ciclo `for`** no debe imprimir nada. Si imprime `FALTA: ...`, activa esa extensión en la Configuración de PHP. La lista sale de lo que piden las librerías en `composer.lock`, más `pdo_mysql` para la base.
- **`proc_open`** lo necesita el respaldo diario (`crea:respaldo-base`).

### Si la terminal usa otra versión de PHP

En Hostinger hay varias versiones de PHP instaladas una junto a otra en `/opt/alt/`. La que eliges en hPanel es la del sitio web, pero la terminal puede seguir usando otra. Si `composer` o `php artisan` corren con una versión vieja, fallan o instalan dependencias equivocadas.

1. Mira qué versiones hay y prueba la que elegiste en hPanel. En los ejemplos es la 8.3: cambia `php83` por `php82`, `php84` o `php85` según tu caso.

   ```bash
   ls /opt/alt/ | grep php
   /opt/alt/php83/usr/bin/php -v
   ```

2. Haz que la terminal use esa versión. Abre `~/.bashrc` con `nano ~/.bashrc` y escribe esto **en la primera línea** (si va más abajo, puede no aplicarse). Guarda con `Ctrl+O`, `Enter` y `Ctrl+X`.

   ```bash
   export PATH=/opt/alt/php83/usr/bin:$PATH
   ```

3. Sal (`exit`), vuelve a entrar por SSH y repite `php -v`: ya debe decir la versión correcta.

4. Comprueba que también la usen los comandos que se mandan por SSH sin entrar al servidor, como los de `deploy.sh`. Desde tu computadora:

   ```bash
   ssh -i ~/.ssh/id_deploy -p 65002 USUARIO@IP_DEL_SERVIDOR "php -v"
   ```

5. Si este último sigue mostrando la versión vieja, en `deploy.sh` busca `cd $REMOTE_DIR &&` en los pasos 4 y 5 y cambia cada uno por esto (la diagonal antes de `$PATH` sí va):

   ```bash
   cd $REMOTE_DIR && export PATH=/opt/alt/php83/usr/bin:\$PATH &&
   ```

   Pon lo mismo después del `cd` en los demás comandos `ssh ... "cd ... && php artisan ..."` de esta guía.

6. Anota la ruta (`/opt/alt/php83/usr/bin/php`): el cron no lee `~/.bashrc`, así que en el paso 12 va completa.

### Si el servidor nuevo es un VPS con Ubuntu 24.04

```bash
sudo apt-get update
sudo apt-get install -y apache2 libapache2-mod-php8.3 php8.3-cli php8.3-mysql php8.3-gd php8.3-zip \
  php8.3-xml php8.3-mbstring php8.3-curl mysql-server mysql-client cron composer git unzip
sudo a2enmod rewrite
```

---

## 3. Base de datos y usuario

En hPanel → Bases de datos → Administración, crea una base nueva y su usuario. Usa una contraseña generada por un gestor de contraseñas. Hostinger les pone un prefijo, por ejemplo `u123456789_crea`. Anota los tres datos: van en el `.env`.

En un VPS:

```bash
sudo mysql -e "CREATE DATABASE crea CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'crea'@'localhost' IDENTIFIED BY 'CONTRASEÑA';
  GRANT ALL PRIVILEGES ON crea.* TO 'crea'@'localhost';"
```

---

## 4. Restaurar el último respaldo

**Restaura antes de subir el código.** `deploy.sh` corre las migraciones. Si corren sobre la base vacía y después restauras encima, el respaldo no borra las tablas que crearon esas migraciones. La siguiente migración falla con «Table already exists».

Desde tu computadora, sube el respaldo a la carpeta personal de la cuenta, fuera de `public_html`:

```bash
scp -i ~/.ssh/id_deploy -P 65002 crea-respaldo-2026-10-05_030000.sql.gz USUARIO@IP_DEL_SERVIDOR:
```

En el servidor, cárgalo en la base nueva. `gunzip -c` lo descomprime al vuelo y no deja un `.sql` sin protección:

```bash
gunzip -c ~/crea-respaldo-2026-10-05_030000.sql.gz | mysql -u USUARIO_BD -p --default-character-set=utf8mb4 NOMBRE_BD
```

Pide la contraseña de la base y no muestra nada si salió bien.

Revisa qué tan reciente es el respaldo:

```bash
mysql -u USUARIO_BD -p NOMBRE_BD -e "SELECT COUNT(*) AS creditos FROM creditos; SELECT MAX(created_at) AS ultimo_pago FROM pagos;"
```

**Guarda también quiénes son administradores y cómo están las modalidades.** El deploy del paso 6 puede cambiar las dos cosas (ver el aviso de ese paso), y con estas listas vas a notar si pasó. Cópialas a un archivo de texto en tu computadora:

```bash
mysql -u USUARIO_BD -p NOMBRE_BD -e "SELECT u.id, u.name, u.email FROM users u JOIN model_has_roles m ON m.model_id = u.id JOIN roles r ON r.id = m.role_id WHERE r.name = 'Administrador' ORDER BY u.id;"
mysql -u USUARIO_BD -p NOMBRE_BD -e "SELECT nombre, tasa_interes, tasa_moratoria, monto_minimo, monto_maximo, plazo_min_meses, plazo_max_meses FROM modalidad_creas;"
```

Las fechas de la base están en **hora UTC**: resta 6 horas para tener la hora de Mérida. Todo lo que se registró después del último pago se perdió y hay que capturarlo de nuevo (sección 17).

---

## 5. El `.env`

El `.env` es el archivo de configuración del sitio: ahí van las contraseñas. No está en el repositorio ni en el respaldo de la base. Lo armas en tu computadora, lo subes a `public_html/.env` y lo proteges.

### De dónde sale

- **Si Diego tiene guardada una copia del `.env` de producción**, usa esa. Cambia solo lo que dice la columna «Qué cambia en un servidor nuevo» de la tabla de abajo.
- **Si no hay copia**, ármalo desde la plantilla del repositorio, `.env.example`. Ahí están todos los nombres, pero con valores de desarrollo. Descárgala en Git Bash:

  ```bash
  curl -o .env.produccion https://raw.githubusercontent.com/diegomtr8-art/iyem-crea/develop/.env.example
  ```

  Ábrela con un editor de texto y cambia:
  - `APP_ENV=local` por `APP_ENV=production`, y `APP_DEBUG=true` por `APP_DEBUG=false`.
  - `APP_URL=http://localhost` por `APP_URL=https://crea.iyemyucatan.com`.
  - `APP_KEY=` vacío: pon la llave de antes o una nueva (ver abajo).
  - `DB_CONNECTION=sqlite` por `DB_CONNECTION=mysql`. Quita el `#` del inicio de las líneas `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`, y pon los datos del paso 3. En Hostinger, `DB_HOST` normalmente es `localhost`; hPanel lo muestra junto a la base.
  - `MAIL_MAILER=log` por los datos del buzón; están explicados en `docs/TAREAS_PROGRAMADAS.md` (J.10), sección 1.

- **Las contraseñas** (correo, Google y la `APP_KEY` anterior) te las da Diego. Pídeselas en persona o por un gestor de contraseñas, nunca por WhatsApp, correo ni el ticket.

### Qué lleva

Estas son las variables que necesita CREA. Aquí van solo los nombres: **nunca escribas los valores en el repositorio, en el ticket ni en un chat**.

| Grupo | Variables | Qué cambia en un servidor nuevo |
|---|---|---|
| Aplicación | `APP_NAME`, `APP_ENV=production`, `APP_KEY`, `APP_DEBUG=false`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `APP_KEY`: usa la de antes si la tienes (ver abajo) |
| Base de datos | `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Los tres datos del paso 3 |
| Correo | `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Si el buzón de envío era de la cuenta de Hostinger perdida, hay que crearlo otra vez y cambiar la contraseña |
| Cola, sesión y caché | `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, `SESSION_LIFETIME`, `CACHE_STORE=database`, `FILESYSTEM_DISK=local` | Nada |
| Registros | `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | Nada |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Nada, si el dominio es el mismo |
| Crédito | `CREA_DIAS_ANIO`, `CREA_DIAS_GRACIA` | Nada |
| Respaldo (J.9) | `RESPALDO_DIRECTORIO`, `RESPALDO_MYSQLDUMP` | La ruta, si cambió el usuario de la cuenta |
| Portal (PR #58) | `PORTAL_ABIERTO` | Nada |

**Sobre `APP_KEY`:** CREA no guarda en la base datos cifrados con esa llave, así que perderla no pierde información. Si no la tienes, o si el servidor fue comprometido, genera una nueva. El único efecto es que todos tienen que volver a iniciar sesión, y dejan de servir los enlaces para recuperar la contraseña o verificar el correo que ya se habían mandado. Para generarla en tu computadora:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Súbelo y protégelo:

```bash
scp -i ~/.ssh/id_deploy -P 65002 .env.produccion USUARIO@IP_DEL_SERVIDOR:domains/crea.iyemyucatan.com/public_html/.env
ssh -i ~/.ssh/id_deploy -p 65002 USUARIO@IP_DEL_SERVIDOR "chmod 600 domains/crea.iyemyucatan.com/public_html/.env"
```

Al terminar todo, borra de tu computadora la copia del `.env`.

---

## 6. Código, dependencias, build, migraciones y seeders

Todo esto lo hace `deploy.sh` desde tu computadora:

1. build del frontend;
2. empaquetado y subida del código;
3. `composer install --no-dev` en el servidor;
4. `php artisan migrate --force`;
5. los seeders de modalidades y de roles y permisos;
6. las cachés y la copia de `build/`.

En Git Bash, clona el repositorio en una carpeta de **ruta corta**, por ejemplo `C:\crea`. En una ruta larga (dentro de OneDrive o del Escritorio, con nombres largos), Windows falla con «Filename too long».

```bash
cd /c
git clone https://github.com/diegomtr8-art/iyem-crea.git crea
cd crea
git checkout develop
composer install
npm ci
```

- **Rama:** usa la rama o el commit que estaba en producción. Pregúntale a Diego (sección 15: hoy no queda registro de cuál es).
- **`composer install` en tu computadora:** hace falta aunque el servidor instale lo suyo, porque el build del frontend toma Ziggy de `vendor/`.

Si el servidor nuevo tiene otro usuario, IP o carpeta, cambia estas líneas al principio de `deploy.sh`. No subas ese cambio al repositorio sin un PR.

```bash
SSH_KEY="${HOME}/.ssh/id_deploy"
SSH_HOST="USUARIO@IP_DEL_SERVIDOR"
REMOTE_DIR='~/domains/crea.iyemyucatan.com/public_html'
```

> **⚠️ Antes de correr `deploy.sh`: puede dejar a alguien como Administrador sin querer.**
>
> `deploy.sh` corre dos seeders que cambian la base restaurada (sección 16):
>
> - **`RoleAndPermissionSeeder`** le da el rol Administrador al primer usuario operativo de la base. En el simulacro, ese usuario era un Cajero y quedó como Administrador.
> - **`ActualizarModalidadesSeeder`** reescribe tasas, montos y plazos de las modalidades con los valores que trae el código.
>
> Antes de seguir, ten a la mano las dos listas que guardaste en el paso 4. Justo después del deploy las vas a comparar («Revisa administradores y modalidades», abajo).

Córrelo:

```bash
bash deploy.sh
```

La primera vez que se conecta, pregunta si confías en el servidor: escribe `yes`. Debe terminar con `✅ Deploy completado exitosamente`. Los «Pasos manuales» que imprime al final están cubiertos en los pasos 5 y 7 a 12 de esta guía.

### Si `deploy.sh` se detiene en `route:cache`

En el simulacro, con `develop` del 16/10/2026, `deploy.sh` se detuvo con este error:

```
Unable to prepare route [acreditados/{acreditado}] for serialization. Another route has already been assigned name [acreditados.update].
```

Las migraciones, los seeders y la caché de configuración ya habían corrido; faltaba el resto del paso 5 de `deploy.sh`. Termínalo a mano, sin la caché de rutas (el sistema funciona igual, solo un poco más lento):

```bash
ssh -i ~/.ssh/id_deploy -p 65002 USUARIO@IP_DEL_SERVIDOR "cd ~/domains/crea.iyemyucatan.com/public_html && php artisan route:clear && php artisan view:cache && php artisan event:cache && rm -rf build && cp -r public/build/ build/"
```

El aviso `tar: PENDIENTES_AUTH.md: Cannot stat` del paso 3 de `deploy.sh` no detiene nada: ese archivo ya no existe en el repositorio.

### Revisa administradores y modalidades

Vuelve a correr las dos consultas del paso 4 y compáralas con lo que guardaste:

- **Si aparece un administrador nuevo**, fue el seeder. En el panel ve a Sistema → Usuarios, edita a esa persona, déjale su rol original y guarda. Hazlo antes de abrir el sistema a los usuarios.
- **Si cambió algún valor de las modalidades**, avísale a Diego con los valores de antes y los de ahora. Él decide cuáles son los correctos.

---

## 7. Documentos y comprobantes (almacenamiento privado)

En `storage/app/private` viven los documentos de las solicitudes, los ZIP de formatos, las comprobaciones de uso y los comprobantes de desembolso. La base solo guarda su ruta.

Si tienes la copia (`storage-privado.tar.gz`), súbela y descomprímela:

```bash
scp -i ~/.ssh/id_deploy -P 65002 storage-privado.tar.gz USUARIO@IP_DEL_SERVIDOR:
ssh -i ~/.ssh/id_deploy -p 65002 USUARIO@IP_DEL_SERVIDOR
tar xzf ~/storage-privado.tar.gz -C ~/domains/crea.iyemyucatan.com/public_html/storage/app
```

Con copia o sin ella, comprueba qué archivos de los que menciona la base existen:

```bash
cd ~/domains/crea.iyemyucatan.com/public_html
php artisan tinker --execute='$r = collect()->merge(DB::table("documentos_solicitud")->pluck("ruta_archivo"))->merge(DB::table("documentos_comprobacion")->pluck("ruta_archivo"))->merge(DB::table("desembolsos")->pluck("comprobante_ruta"))->merge(DB::table("solicitudes_credito")->pluck("formatos_zip_ruta"))->filter()->unique(); $f = $r->reject(fn ($x) => Storage::disk("local")->exists($x)); echo "Archivos en la base: {$r->count()}. Faltan: {$f->count()}\n"; $f->each(fn ($x) => print("  $x\n"));'
```

Debe decir `Faltan: 0`. Si faltan, la lista dice cuáles se perdieron: hay que pedir esos documentos de nuevo a los acreditados.

Después, borra el `.tar.gz` del servidor: tiene datos personales.

---

## 8. Enlace de `storage`

En Hostinger, `php artisan storage:link` no funciona. Usa:

```bash
ln -sfn ~/domains/crea.iyemyucatan.com/public_html/storage/app/public ~/domains/crea.iyemyucatan.com/public_html/public/storage
```

---

## 9. Permisos de carpetas

```bash
cd ~/domains/crea.iyemyucatan.com/public_html
find storage bootstrap/cache -type d -exec chmod 755 {} +
find storage bootstrap/cache -type f -exec chmod 644 {} +
```

En Hostinger, PHP corre con el usuario de la cuenta, así que no hace falta cambiar el dueño. En un VPS donde Apache corre como `www-data`, ese usuario tiene que poder escribir en `storage` y `bootstrap/cache`.

---

## 10. DocumentRoot hacia `public`

El sitio se sirve desde `public_html/public`, nunca desde `public_html`: ahí están el `.env`, el código y `storage`.

**En Hostinger**, la carpeta del sitio es `public_html`. Crea `public_html/.htaccess` con este contenido, que manda todo a `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**En un VPS**, crea `/etc/apache2/sites-available/crea.conf`:

```apache
<VirtualHost *:80>
    ServerName crea.iyemyucatan.com
    DocumentRoot /home/USUARIO/domains/crea.iyemyucatan.com/public_html/public
    <Directory /home/USUARIO/domains/crea.iyemyucatan.com/public_html/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

y actívalo:

```bash
sudo a2ensite crea
sudo a2dissite 000-default
sudo systemctl reload apache2
```

Comprueba desde tu computadora. Si el DNS todavía no apunta, usa `http://IP_DEL_SERVIDOR` en lugar del dominio.

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://crea.iyemyucatan.com/login
curl -s -o /dev/null -w "%{http_code}\n" https://crea.iyemyucatan.com/.env
curl -s -o /dev/null -w "%{http_code}\n" https://crea.iyemyucatan.com/composer.json
```

La primera debe dar `200` y las otras dos `404`. Si `.env` da `200`, **el sitio está exponiendo contraseñas**: no sigas hasta corregir este paso.

---

## 11. SSL

hPanel → Seguridad → SSL: instala el certificado gratuito para `crea.iyemyucatan.com`. Solo se puede cuando el DNS ya apunta al servidor nuevo (paso 1).

---

## 12. Cron del programador y de la cola

**El cron se activa solo con autorización de Diego**: desde ese minuto CREA manda correos reales y recalcula la mora.

Antes de activarlo, revisa la tabla `jobs` de la base restaurada. Puede traer correos que estaban por salir cuando se hizo el respaldo, y saldrían en el primer minuto con días de atraso:

```bash
mysql -u USUARIO_BD -p NOMBRE_BD -e "SELECT COUNT(*) FROM jobs;"
```

En hPanel → Avanzado → Cron Jobs, crea uno que corra cada minuto (`* * * * *`) con este comando:

```
cd /home/USUARIO/domains/crea.iyemyucatan.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Si en el paso 2 la terminal usaba otra versión de PHP, cambia `php artisan` por la ruta completa, por ejemplo `/opt/alt/php83/usr/bin/php artisan`.

Ese único cron corre las tareas diarias y también envía los correos de la cola. El detalle de qué corre y cómo comprobarlo está en `docs/TAREAS_PROGRAMADAS.md` (J.10). Por ahora, `php artisan schedule:list` debe mostrar las tareas con su próxima hora.

---

## 13. Comprobación final

1. `php artisan migrate:status`: todas las migraciones en `Ran`.
2. Entra con un usuario administrador. Las contraseñas son las de antes, porque vienen en la base.
3. Revisa que el panel muestre los créditos y saldos que esperabas. Abre un acreditado, un crédito y sus pagos.
4. Abre un comprobante de desembolso. Los demás documentos ya se revisaron en el paso 7.
5. Abre la página de inicio y el portal ciudadano: deben cargar con su diseño.
6. Revisa que `storage/logs/laravel.log` no tenga errores nuevos.
7. Cuando el cron esté activo, corre el respaldo (`docs/RESPALDO.md`) y copia el primero fuera del servidor.

Anota la hora de fin y avisa en el ticket: cuánto tardó y desde qué fecha hay que recapturar.

---

## 14. Tiempos medidos en el simulacro (RTO)

**Simulacro del 16/10/2026.** Se hizo en un servidor desechable que imita la cuenta de Hostinger:

- **Servidor:** contenedor con Ubuntu 24.04, Apache 2.4, PHP 8.3.6 y MySQL 8.0.46; usuario `u000000001`, SSH por el 65002 y la misma carpeta del sitio.
- **Computadora del deploy:** Windows 11 con Git Bash.
- **Datos:** solo de prueba (`TestDataSeeder`, `Add30CreditosSeeder`, dos operativos y tres desembolsos). Respaldo de 29 KB y 13 archivos privados.
- **Código:** `develop` en `a5d2c6b`.

**Cómo se hizo:** se armó una «producción» de prueba, se sacaron el respaldo, los archivos privados y el `.env`, y se borró el servidor. Después se recuperó todo en un servidor nuevo siguiendo esta guía.

**Resultado:**

- Las 39 tablas quedaron idénticas al original (`CHECKSUM TABLE`), salvo `sessions` (por los inicios de sesión de la prueba) y las dos que cambian los seeders (sección 16).
- Los 13 archivos quedaron idénticos (`sha256sum`).

| Paso | Quién lo hace | Tiempo medido | Tiempo realista en Hostinger | Dependencia |
|---|---|---|---|---|
| 1. Dominio y DNS | Diego | No se puede medir en local | 5 min, más hasta 30 min de propagación | Acceso a la zona DNS; la espera corre en paralelo |
| 2. Sitio, PHP y SSH | Diego (hPanel) y técnico | 4 min 18 s (VPS, instalar con `apt`) + 2 s comprobar | 15 min | Acceso a hPanel |
| 3. Base y usuario | Diego (hPanel) | 1 s | 3 min | Paso 2 |
| 4. Restaurar respaldo | Técnico | 3 s | 5 min, más la subida del archivo | Respaldo fuera del servidor; paso 3 |
| 5. `.env` | Técnico, con los valores de Diego | 1 s | 10 min | Diego |
| 6. Código con `deploy.sh` | Técnico | 3 min 35 s: clonar 5 s, `composer` 54 s, `npm ci` 1 min 57 s, `deploy.sh` 35 s, terminar a mano 2 s | 15 min | Pasos 4 y 5 |
| 7. Documentos y comprobantes | Técnico | 2 s | 5 min, más la subida de la copia | Copia de `storage/app/private` |
| 8. Enlace de `storage` | Técnico | 1 s | 1 min | Paso 6 |
| 9. Permisos | Técnico | 1 s | 1 min | Paso 6 |
| 10. DocumentRoot | Técnico | 4 s (cualquiera de las dos variantes) | 5 min | Paso 2 |
| 11. SSL | Diego (hPanel) | No se puede medir en local | 15 a 30 min | Espera DNS |
| 12. Cron | Técnico, con autorización de Diego | 1 s | 5 min | Autorización de Diego |
| 13. Comprobación | Técnico y alguien del área | 1 min 53 s | 15 min | Todo lo anterior |
| **Total** | | **13 min 31 s de principio a fin** | **≈ 2 horas** | |

- **Tiempo medido:** lo que tardaron los comandos en el simulacro, más el diagnóstico de los dos tropiezos.
- **Tiempo realista:** suma lo que tarda una persona en leer, hacer los clics de hPanel, escribir y esperar.

**RTO (tiempo para volver a operar): unas 2 horas.** Supone que:

- se puede entrar a una cuenta de Hostinger;
- Diego está disponible;
- el respaldo, la copia de documentos y los valores del `.env` ya están fuera del servidor.

Las cuentas son estas:

- El trabajo de los pasos 2 a 10 lleva alrededor de una hora y cubre los 30 minutos de propagación del DNS.
- Después vienen el SSL (15 a 30 min), el cron y la comprobación.

El tiempo cambia en estos casos:

- **Respaldos grandes:** el simulacro usó 29 KB. Un respaldo de cientos de MB o muchos documentos agregan el tiempo de subirlos por SSH.
- **Cuenta de Hostinger perdida:** hay que contratar hosting nuevo y cambiar los nameservers desde el registrador. Eso puede llevar el RTO a **1 o 2 días**.
- **Sin respaldo fuera del servidor:** no hay RTO, porque no hay qué recuperar (sección 17).

---

## 15. Qué no está en el respaldo de la base

El respaldo de la J.9 (`crea:respaldo-base`) solo copia la base de datos. Esto se pierde si se pierde el servidor:

| Qué | Dónde vive hoy | Cómo se respalda hoy | Si se pierde |
|---|---|---|---|
| Documentos de solicitudes, ZIP de formatos, comprobaciones de uso y comprobantes de desembolso | `public_html/storage/app/private` en el servidor | **No tienen respaldo propio.** Solo los respaldos de Hostinger, que viven en la misma cuenta | Hay que pedir los documentos otra vez a los acreditados; los comprobantes de desembolso no se recuperan |
| Comprobantes de desembolso subidos antes del PR #47, si los hay | `public_html/storage/app/public/desembolsos` | Igual que el anterior | Igual. Además, la app ya los busca en el disco privado, así que hoy tampoco abren |
| `.env` (contraseñas de la base y del correo, `GOOGLE_CLIENT_SECRET`) | Solo en el servidor; `deploy.sh` menciona una copia local `.env.production` de quien hace deploys | **No hay copia segura documentada** | Se recrea: base nueva, contraseña nueva del buzón y secreto nuevo en Google Cloud |
| `APP_KEY` | Dentro del `.env` | Igual que el `.env` | No se pierden datos (paso 5); se cierran las sesiones |
| Respaldos de la base | Por omisión, `storage/app/private/respaldos`: **en el mismo servidor** | Copia manual a otro lugar una vez por semana (`docs/RESPALDO.md`, sección 4) | Solo queda la última copia manual |
| `storage/logs/laravel.log` | Servidor | No | Se pierde el historial de errores |
| Configuración del servidor: arreglo de `public_html`, cron, versión de PHP, SSL | hPanel | Solo lo que dicen `deploy.sh`, `docs/TAREAS_PROGRAMADAS.md` y esta guía | Se rehace con esta guía |
| Qué commit está en producción | No queda registro: `deploy.sh` sube la copia local de quien lo corre, incluso con cambios sin commit | — | No se sabe con certeza qué versión reponer |
| Zona DNS | Hostinger, la misma cuenta del servidor | Hostinger | Si se pierde la cuenta, se pierde el control del DNS (paso 1) |

---

## 16. Lo que cambian los seeders en una base restaurada

`deploy.sh` corre dos seeders en cada deploy, y también al recuperar. En el simulacro cambiaron datos de la base restaurada:

- **`RoleAndPermissionSeeder`** le da el rol Administrador al primer usuario operativo que encuentra en la base (normalmente el de id más bajo), si no lo tiene. En el simulacro, ese usuario era un Cajero y quedó como Administrador. Por eso el paso 6 pide revisar a los administradores.
- **`ActualizarModalidadesSeeder`** reescribe las modalidades (tasas, montos, plazos y documentos requeridos) con los valores de las convocatorias 2025 que trae el código. Cualquier cambio que se haya hecho desde el panel se pierde.

Las dos cosas pasan igual en cada deploy normal, no solo al recuperar.

---

## 17. Punto de pérdida máximo (RPO)

El RPO es cuánto trabajo se puede perder: todo lo que se registró después del último respaldo que sobrevive.

**Hoy:**

- **Base de datos:** el respaldo diario de la J.9 todavía no corre en producción, porque está en el PR #40 y el cron está apagado. El único respaldo de la base y de los documentos es el de Hostinger, que guarda la frecuencia de su plan y vive en la misma cuenta.
- **Si se pierde la cuenta:** no hay ninguna copia de CREA fuera de ella, así que se pierde todo.

**Con la J.9 activa y la copia semanal manual de `docs/RESPALDO.md`:**

- **Si se corrompe la base pero el servidor sigue:** se pierde hasta 1 día (el respaldo es diario, a las 3:00).
- **Si se pierde el servidor:** se pierde hasta 1 semana (la última copia manual), más todos los documentos.

**Propuesta para Diego (la decisión es suya): RPO de 24 horas para la base y los documentos.** Para lograrlo:

1. Fusionar la J.9 y activar el cron, con su autorización.
2. Sacar cada día, de forma automática, una copia del respaldo y de `storage/app/private` a un lugar fuera de Hostinger con acceso restringido.
3. Hacer este simulacro cada 6 meses, o después de cambios grandes en el despliegue.

Si un día de pérdida es demasiado, el respaldo puede correr cada 6 horas. Así el RPO baja a 6 horas, a cambio de 4 archivos por día.
