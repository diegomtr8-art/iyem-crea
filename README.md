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
```

### 7. Ejecutar migraciones

```bash
php artisan migrate
```

### 8. Ejecutar seeders

```bash
php artisan db:seed
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
APP_NAME=Laravel
APP_ENV=local
APP_KEY=<generada_con_php_artisan_key:generate>
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=128.0.0.1
DB_PORT=3306
DB_DATABASE=<nombre_de_la_base_de_datos>
DB_USERNAME=root
DB_PASSWORD=

[OTRAS_VARIABLES]
CREA_DIAS_ANIO=360
CREA_DIAS_GRACIA=5
```

## Roles y permisos

| Rol | Descripción | Permisos |
|---|---|---|
| Administrador | Responsable de la gestión y configuración general del sistema. | Ver interesados, acreditados, pagos, reportes, simulador, usuarios y roles |
| Operativo | Responsable de la consulta y gestión de la información relacionada con la operación del programa. | Ver interesados, acreditados, pagos y reportes. |

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

1. Crear una rama:

```bash
git checkout -b [NOMBRE-DE-LA-RAMA]
```

2. Realizar los cambios.
3. Verificar que el proyecto funcione correctamente.
4. Ejecutar las pruebas:

```bash
php artisan test
```

5. Registrar los cambios:

```bash
git add .
git commit -m "[DESCRIPCIÓN DEL CAMBIO]"
```

6. Subir la rama:

```bash
git push origin [NOMBRE-DE-LA-RAMA]
```

7. Crear un Pull Request y describir los cambios.

## Contacto

**Responsable:** Diego Armando Martinez Ruiz

**Correo:** diegomtr8@gmail.com

**Organización:** IYEM

**Repositorio:** https://github.com/diegomtr8-art/iyem-crea

**Otros medios:** [INFORMACIÓN]
