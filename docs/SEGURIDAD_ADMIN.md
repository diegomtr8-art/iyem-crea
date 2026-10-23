# Seguridad del panel de administración

Revisión de quién puede entrar a cada ruta del panel operativo de CREA (ticket J.16). La revisión no se hizo solo leyendo el código. Cada ruta se probó con usuarios de prueba en una base local (nunca en producción, nunca con credenciales reales) y se anotó el código de respuesta.

**Fecha de la revisión:** 21/10/2026, sobre `develop` en `4fb46b8`.

---

## 1. Resumen

Todas estas rutas están en el grupo `auth` + `verified` + `operativo` de `routes/web.php`. El middleware `operativo` (`EsOperativo`) solo revisa que el usuario sea de tipo operativo; **no revisa su rol ni sus permisos**. Ningún controlador lo revisa por dentro: lo único que protege una ruta es el `role:` que tenga.

| | Rutas | Situación |
|---|---|---|
| Rutas de administración | 18 | Exigen `role:Administrador`. `/auditoria` no lo exigía y se corrigió en este PR. |
| Rutas de otros módulos | 64 | **Ninguna exige rol ni permiso.** Un operativo sin rol entra a todas. |

Los hallazgos graves se reportaron a Diego en el ticket antes del PR (sección 3).

**Cómo se probó:** con tres operativos de prueba, uno sin rol, uno con el rol Cobranza y uno Administrador, cada uno en una base nueva con datos de los seeders del proyecto.

- **403:** la ruta le negó la entrada.
- **200:** vio la pantalla o descargó el archivo.
- **302:** la acción se aceptó y lo regresó a la página anterior. En las rutas `POST`, `PUT` y `DELETE` eso quiere decir que el cambio sí se hizo en la base de prueba.

**Sobre «Debería exigir»:** los permisos salen de `RoleAndPermissionSeeder`. Donde el seeder no tiene un permiso para esa acción (condonar, reestructurar), se propone `role:Administrador`. Hoy `permission:` no se puede usar en las rutas: en `bootstrap/app.php` solo está registrado el alias `role:`, y una ruta con `permission:...` daría error 500 (sección 3.4).

---

## 2. Inventario

### Rutas de administración (18)

| Método | Ruta | Controlador | Middleware hoy | Debería exigir | ¿Lo exige hoy? | Sin rol / Cobranza / Admin |
|---|---|---|---|---|---|---|
| GET | `admin/tareas-programadas` | `BitacoraTareasController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| GET | `admin/correos-fallidos` | `CorreosFallidosController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| POST | `admin/correos-fallidos/reintentar-todos` | `CorreosFallidosController@reintentarTodos` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| POST | `admin/correos-fallidos/{fallo}/reintentar` | `CorreosFallidosController@reintentar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| POST | `admin/correos-fallidos/{fallo}/descartar` | `CorreosFallidosController@descartar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| GET | `admin/recordatorios` | `RecordatoriosAdminController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| POST | `admin/recordatorios` | `RecordatoriosAdminController@enviar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| GET | `admin/mensajes-ciudadanos` | `Admin\MensajesCiudadanoController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| GET | `admin/mensajes-ciudadanos/{mensaje}` | `Admin\MensajesCiudadanoController@show` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| POST | `admin/mensajes-ciudadanos/{mensaje}/atendido` | `Admin\MensajesCiudadanoController@marcarAtendido` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| GET | `admin/importar-acreditados` | `ImportarAcreditadosController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| GET | `admin/importar-acreditados/plantilla` | `ImportarAcreditadosController@plantilla` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| POST | `admin/importar-acreditados/previsualizar` | `ImportarAcreditadosController@previsualizar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| POST | `admin/importar-acreditados/confirmar` | `ImportarAcreditadosController@confirmar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| POST | `admin/importar-acreditados/cancelar` | `ImportarAcreditadosController@cancelar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| POST | `admin/notificaciones-tareas/leidas` | `NotificacionesTareasController@marcarLeidas` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 302 |
| GET | `admin/respaldo-excel` | `RespaldoExcelController@descargar` | operativo + `role:Administrador` | `role:Administrador` | Sí | 403 / 403 / 200 |
| GET | `auditoria` | `AuditoriaController@index` | operativo + `role:Administrador` | `role:Administrador` | Sí (corregida en este PR) | 403 / 403 / 200 |

### Rutas de otros módulos (64)

| Método | Ruta | Controlador | Middleware hoy | Debería exigir | ¿Lo exige hoy? | Sin rol / Cobranza / Admin |
|---|---|---|---|---|---|---|
| GET | `dashboard` | `DashboardController@index` | operativo | `permission:dashboard.ver` | **No** | 200 / 200 / 200 |
| GET | `acreditados` | `AcreditadoController@index` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `acreditados/create` | `AcreditadoController@create` | operativo | `permission:acreditados.gestionar` | **No** | 200 / 200 / 200 |
| POST | `acreditados` | `AcreditadoController@store` | operativo | `permission:acreditados.gestionar` | **No** | 302 / 302 / 302 |
| GET | `acreditados/{acreditado}` | `AcreditadoController@show` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `acreditados/{acreditado}/edit` | `AcreditadoController@edit` | operativo | `permission:acreditados.gestionar` | **No** | 200 / 200 / 200 |
| PUT | `acreditados/{acreditado}` | `AcreditadoController@update` | operativo | `permission:acreditados.gestionar` | **No** | 302 / 302 / 302 |
| DELETE | `acreditados/{acreditado}` | `AcreditadoController@destroy` | operativo | `permission:acreditados.gestionar` | **No** | 302 / 302 / 302 |
| GET | `acreditados/{acreditado}/expediente` | `AcreditadoController@expediente` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `acreditados/{acreditado}/operaciones` | `OperacionesController@index` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `api/notificaciones/cuotas` | `NotificacionController@cuotasManana` | operativo | `permission:cobranza.ver` | **No** | 200 / 200 / 200 |
| GET | `cobranza` | `CobranzaController@index` | operativo | `permission:cobranza.ver` | **No** | 200 / 200 / 200 |
| GET | `cobranza/{credito}` | `CobranzaController@show` | operativo | `permission:cobranza.ver` | **No** | 200 / 200 / 200 |
| POST | `cobranza/{credito}/gestion` | `CobranzaController@storeGestion` | operativo | `permission:cobranza.gestionar` | **No** | 302 / 302 / 302 |
| POST | `cobranza/{credito}/juridico` | `CobranzaController@enviarJuridico` | operativo | `permission:cobranza.gestionar` | **No** | 302 / 302 / 302 |
| GET | `comprobaciones` | `ComprobacionController@index` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `comprobaciones/{comprobacion}` | `ComprobacionController@show` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| POST | `comprobaciones/{comprobacion}/aprobar` | `ComprobacionController@aprobar` | operativo | `permission:acreditados.gestionar` | **No** | 302 / 302 / 302 |
| POST | `comprobaciones/{comprobacion}/rechazar` | `ComprobacionController@rechazar` | operativo | `permission:acreditados.gestionar` | **No** | 302 / 302 / 302 |
| GET | `creditos/{credito}/condonacion-formal` | `CondonacionFormalController@create` | operativo | `role:Administrador` | **No** | 200 / 200 / 200 |
| POST | `creditos/{credito}/condonacion-formal` | `CondonacionFormalController@store` | operativo | `role:Administrador` | **No** | 302 / 302 / 302 |
| POST | `creditos/{credito}/condonar` | `CreditoController@condonar` | operativo | `role:Administrador` | **No** | 302 / 302 / 302 |
| GET | `creditos/{credito}/reestructurar` | `ReestructuracionController@create` | operativo | `role:Administrador` | **No** | 200 / 200 / 200 |
| POST | `creditos/{credito}/reestructurar` | `ReestructuracionController@store` | operativo | `role:Administrador` | **No** | 302 / 302 / 302 |
| GET | `creditos/{credito}/dictamen` | `CreditoController@dictamen` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/dictamen/pdf` | `CreditoController@dictamenPdf` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/contrato/pdf` | `CreditoController@contratoPdf` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/estado-cuenta/pdf` | `CreditoController@estadoCuentaPdf` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/constancia/pdf` | `CreditoController@constanciaPdf` | operativo | `permission:acreditados.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/desembolso` | `DesembolsoController@create` | operativo | `permission:desembolsos.gestionar` | **No** | 200 / 200 / 200 |
| POST | `creditos/{credito}/desembolso` | `DesembolsoController@store` | operativo | `permission:desembolsos.gestionar` | **No** | 302 / 302 / 302 |
| GET | `desembolso/{desembolso}/comprobante` | `DesembolsoController@descargarComprobante` | operativo | `permission:desembolsos.ver` | **No** | 200 / 200 / 200 |
| GET | `creditos/{credito}/pagos/create` | `PagoController@create` | operativo | `permission:pagos.registrar` | **No** | 200 / 200 / 200 |
| POST | `creditos/{credito}/pagos` | `PagoController@store` | operativo | `permission:pagos.registrar` | **No** | 302 / 302 / 302 |
| POST | `pagos/{pago}/cancelar` | `PagoController@cancelar` | operativo | `permission:pagos.cancelar` | **No** | 302 / 302 / 302 |
| GET | `pagos/{pago}/recibo` | `PagoController@recibo` | operativo | `permission:pagos.ver` | **No** | 200 / 200 / 200 |
| GET | `pagos/{pago}/recibo/pdf` | `PagoController@recibosPdf` | operativo | `permission:pagos.ver` | **No** | 200 / 200 / 200 |
| GET | `exportar/cartera` | `ExportController@cartera` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `exportar/pagos` | `ExportController@pagos` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `exportar/movimientos/{acreditado}` | `ExportController@movimientosAcreditado` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `reportes/cartera` | `ReporteController@cartera` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `reportes/pagos` | `ReporteController@pagos` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `reportes/adeudo/{credito}` | `ReporteController@adeudo` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `reportes/beneficiarios` | `ReporteController@beneficiarios` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `reportes/informe-cobranza` | `ReporteController@informeCobranza` | operativo | `permission:reportes.ver` | **No** | 200 / 200 / 200 |
| GET | `simulador` | `SimuladorController@index` | operativo | `permission:simulador.ver` | **No** | 200 / 200 / 200 |
| GET | `roles` | `RoleController@index` | operativo | `permission:roles.ver` | **No** | 200 / 200 / 200 |
| POST | `roles` | `RoleController@store` | operativo | `permission:roles.gestionar` | **No** | 302 / 302 / 302 |
| PUT | `roles/{role}` | `RoleController@update` | operativo | `permission:roles.gestionar` | **No** | 302 / 302 / 302 |
| DELETE | `roles/{role}` | `RoleController@destroy` | operativo | `permission:roles.gestionar` | **No** | 302 / 302 / 302 |
| GET | `users` | `UserController@index` | operativo | `permission:usuarios.ver` | **No** | 200 / 200 / 200 |
| POST | `users` | `UserController@store` | operativo | `permission:usuarios.gestionar` | **No** | 302 / 302 / 302 |
| PUT | `users/{user}` | `UserController@update` | operativo | `permission:usuarios.gestionar` | **No** | 302 / 302 / 302 |
| DELETE | `users/{user}` | `UserController@destroy` | operativo | `permission:usuarios.gestionar` | **No** | 302 / 302 / 302 |
| GET | `solicitudes` | `SolicitudOperativoController@index` | operativo | `permission:solicitudes.ver` | **No** | 200 / 200 / 200 |
| GET | `solicitudes/{solicitud}` | `SolicitudOperativoController@show` | operativo | `permission:solicitudes.ver` | **No** | 200 / 200 / 200 |
| POST | `solicitudes/{solicitud}/cambiar-estatus` | `SolicitudOperativoController@cambiarEstatus` | operativo | `permission:solicitudes.gestionar` | **No** | 302 / 302 / 302 |
| POST | `solicitudes/{solicitud}/asignar` | `SolicitudOperativoController@asignar` | operativo | `permission:solicitudes.gestionar` | **No** | 302 / 302 / 302 |
| POST | `solicitudes/documento/{documento}/revisar` | `SolicitudOperativoController@revisarDocumento` | operativo | `permission:solicitudes.gestionar` | **No** | 302 / 302 / 302 |
| POST | `solicitudes/{solicitud}/anuncio` | `SolicitudOperativoController@enviarAnuncio` | operativo | `permission:solicitudes.gestionar` | **No** | 302 / 302 / 302 |
| GET | `solicitudes/{solicitud}/registrar-credito` | `SolicitudOperativoController@registrarCredito` | operativo | `permission:solicitudes.aprobar` | **No** | 200 / 200 / 200 |
| POST | `solicitudes/{solicitud}/registrar-credito` | `SolicitudOperativoController@guardarCredito` | operativo | `permission:solicitudes.aprobar` | **No** | 302 / 302 / 302 |
| GET | `solicitudes/{solicitud}/analisis` | `AnalisisCreditoController@create` | operativo | `permission:analisis.gestionar` | **No** | 200 / 200 / 200 |
| POST | `solicitudes/{solicitud}/analisis` | `AnalisisCreditoController@store` | operativo | `permission:analisis.gestionar` | **No** | 302 / 302 / 302 |

`acreditados.update` aparece una sola vez: está declarada dos veces en `routes/web.php` (en el `resource` y en un `PUT` aparte) y las dos apuntan a la misma URL. Esa duplicación es la que rompe `php artisan route:cache` (ver `docs/RECUPERACION_DESASTRE.md`).

En los `DELETE` de otros módulos, el 302 del usuario sin rol quiere decir que **sí borró** el registro de prueba.

---

## 3. Hallazgos en módulos de otras personas

Según el ticket, no se corrigen aquí: se reportan con la ruta y la evidencia (la tabla de la sección 2 y la prueba de la sección 8). La corrección propuesta es agregar a cada ruta el `role:` o `permission:` de la columna «Debería exigir».

### 3.1 Graves: avisados a Diego en el ticket antes del PR

- **Usuarios y roles** (`users`, `roles`):
  - `GET /users` muestra a todos los usuarios, ciudadanos incluidos, con su correo.
  - `POST`, `PUT` y `DELETE` se aceptan. Cualquier operativo puede crear usuarios con cualquier rol, cambiarle a cualquiera el correo, la contraseña o el rol (incluido a sí mismo, para volverse Administrador) y borrar usuarios.
  - En `roles`, cualquier operativo puede crear, editar y borrar roles y sus permisos.
  - **Propuesta:** `role:Administrador` en los dos `Route::resource`.
- **Exportaciones**: cualquier operativo descarga los Excel. Ninguna descarga queda en `auditoria_log`.
  - `exportar/cartera`: la cartera completa, con nombre, municipio, montos, saldos, interés y mora cobrados.
  - `exportar/pagos`: los pagos, con acreditado, contrato, referencia, montos y cajero.
  - `exportar/movimientos/{acreditado}`: los movimientos de un acreditado.
  - **Propuesta:** `permission:reportes.ver`.

### 3.2 Operaciones con dinero

Con 302, la operación se hizo:

- **Pagos:** registrar, cancelar, ver y descargar recibos (`pagos.registrar`, `pagos.cancelar`, `pagos.ver`).
- **Condonación, condonación formal y reestructuración:** cualquier operativo las aplica. El seeder no tiene un permiso para estas acciones; se propone `role:Administrador`.
- **Desembolsos:** registrar un desembolso y descargar su comprobante, que trae datos bancarios (`desembolsos.gestionar`, `desembolsos.ver`).

### 3.3 Datos personales y trámites

- **Acreditados:** lista, ficha y expediente (CURP, RFC, domicilio), además de crear, editar y borrar (`acreditados.ver`, `acreditados.gestionar`).
- **Solicitudes y análisis:**
  - ver, cambiar estatus, asignar, revisar documentos, mandar avisos y registrar el crédito (`solicitudes.ver`, `solicitudes.gestionar`, `solicitudes.aprobar`);
  - capturar el análisis crediticio (`analisis.gestionar`).
- **Comprobaciones de uso:** ver, aprobar y rechazar. No hay permiso propio; se propone `acreditados.ver` y `acreditados.gestionar`.
- **Cobranza:** ver, registrar gestiones y enviar a jurídico (`cobranza.ver`, `cobranza.gestionar`).
- **Solo lectura:** panel, reportes, simulador y cuotas de mañana (`dashboard.ver`, `reportes.ver`, `simulador.ver`, `cobranza.ver`).
  - Ojo con `api/notificaciones/cuotas`: la campana del encabezado la pide para **todos** los operativos. Si se protege, la campana no debe pedirla a quien no tenga el permiso.

### 3.4 Antes de usar `permission:` en las rutas

Estas son decisiones de Diego. No se cambian aquí, porque el ticket deja fuera el modelo de roles y permisos:

1. **Registrar el alias.** En `bootstrap/app.php` solo está `role`. Para usar `permission:...` hay que agregar `'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class`; sin eso la ruta responde 500.
2. **Elegir un solo juego de permisos.** Hoy hay dos:
   - El menú lateral (`AppSidebar.vue`) revisa `ver.acreditados`, `ver.reportes`, etc., que crea `RolesPermissionsSeeder` (el de `db:seed`).
   - `RoleAndPermissionSeeder` (el que pide correr el README) crea `acreditados.ver`, `pagos.registrar`, etc., y cada vez que corre le deja al Administrador solo estos. El `deploy.sh` de `develop` ya no lo corre desde el PR #61; el de `main` sí, en cada deploy. La J.17 (rama `fix/crea-seeder-roles-deploy`) hace que ya no le quite permisos a ningún rol.

   Los roles Cajero, Cobranza, Jurídico, Analista y Consulta no tienen ningún `ver.*`. No ven casi nada en el menú, pero hoy pueden entrar a cualquier ruta escribiendo la URL.

---

## 4. Descargas

| Descarga | ¿Dónde queda el archivo? | ¿Nombre adivinable? | ¿Queda en `auditoria_log`? |
|---|---|---|---|
| Respaldo Excel (`admin/respaldo-excel`) | En ningún lado: se arma en memoria y se manda directo | No aplica, no se guarda | **Sí, desde este PR**: usuario, IP y nombre del archivo |
| Respaldo de la base (`crea:respaldo-base`) | `storage/app/private/respaldos` o `RESPALDO_DIRECTORIO`, fuera de `public`, con un `.htaccess` que niega el acceso | Sí (fecha y hora), pero no hay ninguna ruta web que lo sirva: solo se baja por SSH o desde hPanel | No aplica: no se descarga por la web |
| Exportaciones `exportar/*` | En ningún lado: se mandan directo | No aplica | **No** (sección 3.1). El de movimientos lleva en el nombre el nombre completo del acreditado |
| Contrato y constancia PDF | Se generan al vuelo | No aplica | Sí (`CreditoController`) |
| Comprobante de desembolso | Disco privado (`storage/app/private/desembolsos`) | No se sirve sin pasar por la ruta | **No** |
| Plantilla del importador | Se genera al vuelo | No aplica | No hace falta: no trae datos personales |

Recomendación para el respaldo de la base: en producción, que `RESPALDO_DIRECTORIO` apunte fuera de `public_html`, como dice `docs/RESPALDO.md`.

---

## 5. Lo que muestran las pantallas

| Pantalla | Qué muestra | Situación |
|---|---|---|
| Correos fallidos (J.12) | El correo del destinatario enmascarado y el error resumido, sin rutas del servidor. El *payload* del job no se muestra. | Bien. Lo cubren las pruebas de `CorreosFallidosTest`. |
| Recordatorios de pago | Solo conteos, sin correos | Bien |
| Mensajes de ciudadanos | Correo completo del ciudadano | Solo la ve el Administrador, que lo necesita para responder |
| Bitácora de auditoría | IP, usuario y valores antes y después de créditos y pagos | Desde este PR, solo Administrador |
| Usuarios (`/users`) | Todos los usuarios, ciudadanos incluidos, con correo, a cualquier operativo | **Hallazgo grave** (sección 3.1) |
| Importador | Mensajes de error genéricos («No se pudo leer el archivo…») | Bien |

Con `APP_DEBUG=false` (sección 7), un error no muestra rutas del servidor, consultas SQL ni la traza.

---

## 6. Archivos subidos

- **Importador de acreditados:**
  - Solo acepta `.xlsx` o `.xls` de hasta 2 MB. El tipo se revisa por el contenido, no por la extensión: un texto renombrado a `.xlsx` se rechaza.
  - El archivo **no se guarda**: se lee del temporal de la subida, que PHP borra al terminar la petición. Lo comprueba `SeguridadRutasAdminTest`.
  - Ojo: la vista previa, con nombres y CURP de las filas, se guarda en la sesión hasta que se confirma, se cancela o la sesión vence (120 min). Las sesiones están en la tabla `sessions` sin cifrar (`SESSION_ENCRYPT=false`). Proponer `SESSION_ENCRYPT=true` es decisión de Diego.
- **Respaldo de la base:** no recibe archivos. Al terminar, bien o mal, borra sus temporales (`.sql`, credenciales y `.part`).

---

## 7. Modo de depuración y rutas de prueba

- **`APP_DEBUG=false`:** está indicado para producción en `docs/RECUPERACION_DESASTRE.md` (paso 5). `.env.example` trae `true` porque es para desarrollo.
- **Rutas de depuración:** no hay ninguna entre las 134 rutas del sistema.
- **`public/`:** en `develop` solo tiene `index.php`; el PR #36 quitó `opcache_reset.php`. Pero **`main` todavía tiene `public/debug_paso8.php`** (muestra columnas de la base y la versión de PHP) **y `public/opcache_reset.php`**. Si producción corre `main` o una versión anterior, siguen publicados.
- **Resto de depuración en el portal:** `Portal\WizardSolicitudController` escribe cada paso del asistente, y cada error con su traza completa, en `storage/logs/debug_aval.txt`, sin límite de tamaño. No es una ruta pública, pero conviene quitarlo. Es de otro módulo y se reporta.

---

## 8. Pruebas

```bash
php artisan test tests/Feature/SeguridadRutasAdminTest.php
```

- **Rutas de administración:** sin rol y con Cobranza deben recibir 403, y el Administrador debe entrar. **Si alguna deja de exigir su rol, la prueba falla.**
- **Rutas de otros módulos:** la prueba es la misma, pero mientras el hueco siga abierto queda como **incompleta**, con el código que respondió (por ejemplo: «Debería exigir pagos.cancelar; hoy sin rol responde 302»). No tumba la suite. Cuando el dueño del módulo proteja la ruta, la prueba pasa sola, y conviene moverla a la lista de administración para que una regresión la haga fallar.
- **Cobertura:**
  - Si alguien agrega una ruta al grupo operativo sin ponerla en una de las dos listas, la prueba falla.
  - También falla si se agrega un `role:Administrador` que no esté en la lista de administración.
- **Descargas y archivos:**
  - la descarga del respaldo Excel queda en `auditoria_log` y no deja archivos;
  - el importador rechaza CSV, texto disfrazado de `.xlsx` y archivos de más de 2 MB, sin guardar nada.

Para que el 403 refleje permisos y no una regla del negocio, las pantallas que exigen un estado reciben un registro en ese estado: crédito liquidado, solicitud aprobada o en revisión. Además, la prueba revisa que el Administrador nunca reciba 403.

---

## 9. Qué se corrigió en este PR

Solo lo de las tareas propias:

1. **`GET /auditoria`** ahora exige `role:Administrador`. El seeder le da `auditoria.ver` solo a ese rol. El enlace del menú lateral pasó al bloque de Administrador, para que nadie más vea un enlace que le daría 403.
2. **Respaldo Excel:** cada descarga queda en `auditoria_log` con el usuario y el nombre del archivo. En la pantalla de auditoría se puede filtrar por la entidad `RespaldoExcel`.
3. **Pruebas:** `tests/Feature/SeguridadRutasAdminTest.php`.
