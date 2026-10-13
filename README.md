# Créditos para el Renacimiento de Emprendedores y Artesanos (CREA)

## Qué es

CREA es un programa del IYEM orientado a facilitar el financiamiento para proyectos productivos en Yucatán. Su propósito es impulsar el crecimiento y fortalecimiento de negocios y talleres mediante esquemas de créditos accesibles. Está dirigido principalmente a personas emprendedoras, artesanas y aquellas que desarrollan proyectos con enfoque sustentable del estado.

## Portales

### Portal Ciudadano

Dirigido a las personas solicitantes y acreditadas del programa. Permite consultar y gestionar información relacionada con la solicitud, expediente, crédito, estado de cuenta y comprobación del uso del recurso.

### Panel operativo

Dirigido al personal encargado de la administración y seguimiento del programa. Permite gestionar la información relacionada con acreditados, créditos, pagos, desembolsos y cobranza, así como consultar y generar reportes.

## Stack

- Lenguaje(s): HTML, PHP, TypeScript, JavaScript
- Framework backend: Laravel
- Framework/librería frontend: Vue.js, Tailwind CSS
- Base de datos: MySQL
- Herramientas adicionales: Composer, npm, XAMPP, Git, GitHub, DOMPDF, PhpSpreadSheet, VSCode
- Otras tecnologías: Blade, Inertia.js

## Requisitos

- PHP: 8.2+
- Node.js: LTS 20 +
- npm: 12+
- Composer: 2.8+
- MySQL/MariaDB: [10.4+]
- Otros: Git: 2.52+, Laravel: 12+, XAMPP: 3+, Inertia 2, Vue 3+

## Instalación paso a paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/diegomtr8-art/iyem-crea
cd iyem-crea
```

### 2. Instalar dependencias del backend

```bash
composer install
```

### 3. Instalar dependencias del frontend

```bash
npm install
```

### 4. Configurar las variables de entorno

```bash
copy .env.example .env
```

### 5. Generar configuraciones necesarias

```bash
php artisan key:generate
```

### 6. Configurar la base de datos

Inicia Apache y MySQL desde Xampp. Posteriormente, accede a phpMyAdmin y crea una nueva base de datos con el nombre del proyecto.

Configura la conexión a la base de datos en el archivo .env
```bash
DB_DATABASE=<nombre_de_la_bd>
DB_USERNAME=root
DB_PASSWORD=
DB_CONNECTION=mysql
```
Es necesario que se cambie la conexión de sqlite a mysql de lo contrario no será posible levantar la base de datos correctamente.

### 7. Ejecutar migraciones

```bash
php artisan migrate
```

### 8. Ejecutar seeders

Ejecitar los seeders generales del proyecto: 

```bash
php artisan db:seed
```

Ejecutar el seeder de roles y permisos:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

Para cargar datos adicionales utilizados para pruebas y desarrollo, se pueden ejecutar los siguientes seeders según sea necesario:

```bash
php artisan db:seed --class=ActualizarModalidadesSeeder
php artisan db:seed --class=Add30CreditosSeeder
php artisan db:seed --class=TestCreditosSeeder
php artisan db:seed --class=TestDataSeeder
```

### 9. Compilar los recursos

```bash
npm run build
```

### 10. Iniciar el proyecto

```bash
npm run dev
php artisan serve
```

URL local: http://127.0.0.1:8000/

## Variables de entorno

```env
APP_NAME=IYEM-CREA
APP_ENV=local
APP_KEY=<generada_con_php_artisan_key:generate>
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<nombre_de_la_base_de_datos>
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=servidor-de-correo
MAIL_PORT=587
MAIL_USERNAME=correo@dominio.com
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="correo@dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

CREA_DIAS_ANIO=360
CREA_DIAS_GRACIA=5
```

## Roles y permisos

| Rol | Descripción | Permisos |
|---|---|---|
| Administrador | Responsable de la gestión y configuración general del sistema. | Acceso total al sistema, incluyendo interesados, solicitudes, acreditados, análisis crediticio, desembolsos, pagos, cobranza, jurídico, presupuesto, reportes, simulador, auditoría, usuarios y roles. |
| Operativo | Responsable de la consulta y gestión de la información relacionada con la operación del programa. | Consultar el panel principal, interesados, acreditados, pagos, reportes y simulador. |
| Analista de Crédito | Responsable de la evaluación y gestión de solicitudes de crédito. | Ver y gestionar interesados y solicitudes; aprobar o rechazar solicitudes; consultar acreditados y desembolsos; gestionar análisis crediticios; consultar reportes y utilizar el simulador |
| Cajero | Responsable del registro y consulta de pagos relacionados con los créditos | Consultar acreditados, pagos y cobranza; registrar pagos y consultar reportes. |
| Cobranza | Responsable del seguimiento y gestión de pagos y procesos de cobranza. | Consultar acreditados; consultar, registrar y cancelar pagos; ver y gestionar cobranza; consultar información jurídica y reportes. |
| Jurídico | Responsable del seguimiento de los asuntos jurídicos relacionados con los creditos y procesos de cobranza. | Consultar acreditados; ver y gestionar cobranza; consultar y editar información jurídica; consultar reportes. |
| Consulta | Usuario con acceso de solo lectura a la información general del sistema | Consultar interesados, solicitudes, acreditados, pagos, cobranza y reportes, así como utilizar el simulador. |
| Ciudadano | Usuario externo que accede a las funcionalidades destinadas a los ciudadanos. | Acceso a las funcionalidades correspondientes a ciudadanos, controlado mediante el tipo de usuario y el middleware del sistema. |

## Módulos

### Acreditado

Permite consultar y gestionar la información de las personas beneficiarias que cuentan con un crédito otorgado por el programa.

**Tabla utilizada**: acreditados

### Amortización

Permite llevar el control de las cuotas de cada crédito, incluyendo fechas de vencimiento, saldos, capital, intereses y estado de los pagos.

**Tablas utilizadas**: amortizaciones, creditos

### Análisis Crédito

Permite evaluar las solicitudes de crédito mediante la validación de requisitos, información financiera y criterios de evaluación para determinar una recomendación sobre la solicitud.

**Tablas utilizadas**: analisis_credito, solicitudes_credito

### Anuncio ciudadano

Permite gestionar avisos y notificaciones dirigidos a los usuarios del portal ciudadano.

**Tablas utilizadas**: anuncios_ciudadano, users


### Auditoría

Permite mantener un registro de las acciones y modificaciones realizadas en el sistema para facilitar el seguimiento de los cambios efectuados.

**Tablas utilizadas**: auditoria_log, users

### Comprobación Uso

Permite registrar y revisar la documentación presentada por los acreditados para comprobar el uso de los recursos otorgados mediante el crédito.

**Tablas utilizadas**: comprobaciones_uso, documentos_comprobacion, creditos, solicitudes_credito, acreditados, users

### Condonación Formal

Permite registrar las condonaciones autorizadas sobre capital, interés o mora de un crédito, junto con su motivo y datos de autorización.

**Tablas utilizadas**: condonaciones_formales, creditos

### Crédito

Permite administrar la información y condiciones de los créditos otorgados, incluyendo montos, plazos, tasas, contratos y estado del crédito.

**Tablas utilizadas**: creditos, acreditados, modalidad_creas, solictudes_credito

### Desembolso

Permite registrar las condonaciones autorizadas sobre capital, interés o mora de un crédito, junto con su motivo y datos de autorización.

**Tablas utilizadas**: desembolsos, creditos, users

### Expediente Jurídico

Permite llevar el seguimiento de los créditos que han sido enviados a un proceso jurídico y registrar la información correspondiente al expediente.

**Tablas utilizadas**: expedientes_juridicos, creditos, acreditados, users

### Gestión Cobranza

Permite registrar y dar seguimiento a las acciones de cobranza realizadas sobre los créditos, incluyendo resultados y compromisos de pago.

**Tablas utilizadas**: gestiones_cobranza, creditos, acreditados, users

### Interesado

Permite registrar, consultar y dar seguimiento a las personas interesadas en acceder a los créditos del programa CREA.

**Tablas utilizadas**: interesados

### Modalidad Crea

Permite administrar las características y requisitos de las diferentes modalidades de crédito del programa, como montos, tasas, plazos y criterios de elegibilidad.

**Tablas utilizadas**: modalidad_creas

### Pago

Permite registrar y consultar los pagos realizados por los acreditados, así como su aplicación al capital, intereses y otros conceptos correspondientes al crédito.

**Tablas utilizadas**: pagos, creditos, acreditados, amortizaciones, users

### Presupuesto Programa

Permite llevar el control del presupuesto autorizado para las diferentes modalidades de crédito de acuerdo con el ejercicio fiscal correspondiente.

**Tablas utilizadas**: presupuesto_programa, modalidad_creas, users

### Reestructuración

Permite registrar modificaciones autorizadas a las condiciones de un crédito, como cambios ene el plazo, tasa de interés o fecha de inicio de pagos.

**Tablas utilizadas**: reestructuraciones, creditos

### Solicitud Crédito

Permite gestionar las solicitudes de crédito realizadas por los ciudadanos, incluyendo la información proporcionada, documentación, avales y seguimiento del estado de la solicitud.

**Tablas utilizadas**: solicitudes_credito, solicitud_estatus_historial, documentos_solicitud, avales_solictud, modalidad_creas, users

### User

Permite mantener un registro de las acciones y modificaciones realizadas en el sistema para facilitar el seguimiento de los cambios efectuados.

**Tabla utilizada**: users

## Cómo contribuir

1. Crea la rama asignada para la tarea. Las ramas siguen las convenciones feature/, fix/ o docs/, según el tipo de cambio:

```bash
git checkout -b feature/gestion-solicitudes
```

2. Realizar los cambios.
3. Verificar que el proyecto funcione correctamente.
4. Ejecutar las pruebas:

```bash
php artisan test
```

5. Registrar los cambios los cambios utilizando el formato tipo(área): descripción:

```bash
git add .
git commit -m "feat(solicitudes): agregar validación de documentos"
```

6. Subir la rama al repositorio remoto:

```bash
git push origin feature/gestion-solicitudes
```

7. Crear un Pull Request de la rama de trabajo hacia develop y describir los cambios realizados.

## Contacto

**Responsable:** Diego Armando Martinez Ruiz

**Correo:** diegomtr8@gmail.com

**Organización:** IYEM

**Repositorio:** https://github.com/diegomtr8-art/iyem-crea

