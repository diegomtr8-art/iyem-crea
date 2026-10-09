# Pendientes — Módulo de Autenticación CREA

Fecha de última actualización: 2026-06-10

---

## 1. Google OAuth (BLOQUEANTE para producción)

Para que el botón "Continuar con Google" funcione en producción, Diego debe:

1. Ir a https://console.cloud.google.com
2. Crear (o seleccionar) un proyecto
3. Habilitar la API "Google+ API" o "Google Identity"
4. Crear credenciales OAuth 2.0 → Aplicación web
5. Agregar la URI de redirect autorizada:
   - **Producción:** `https://crea.iyemyucatan.com/auth/google/callback`
   - **Local:** `http://localhost/auth/google/callback`
6. Copiar el Client ID y Client Secret al `.env` del servidor:

```
GOOGLE_CLIENT_ID=tu-client-id-aqui.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret-aqui
GOOGLE_REDIRECT_URI=https://crea.iyemyucatan.com/auth/google/callback
```

> **Nota:** El resto del flujo de auth (login, registro, recuperación de contraseña)
> funciona sin estas credenciales. Solo el botón de Google está bloqueado.

---

## 2. Definición final de la matriz de roles y permisos

El seeder `RoleAndPermissionSeeder` crea los siguientes roles operativos con
permisos preliminares:

| Rol | Permisos principales |
|-----|---------------------|
| Administrador | Todos los permisos |
| Analista de Crédito | Interesados, solicitudes, análisis, reportes |
| Cajero | Pagos (ver/registrar), acreditados, reportes |
| Cobranza | Pagos (cancelar), cobranza, jurídico (ver) |
| Jurídico | Cobranza, jurídico (editar) |
| Consulta | Solo lectura en todos los módulos |
| Ciudadano | Controlado por middleware, sin permisos Spatie |

**Diego debe revisar y ajustar** los permisos asignados a cada rol según los
procesos reales del IYEM, especialmente:
- ¿Quién puede aprobar/rechazar solicitudes?
- ¿Quién puede cancelar pagos?
- ¿El analista puede ver datos de cobranza jurídica?

Para re-ejecutar el seeder en producción (usa `updateOrCreate`, no trunca):
```bash
php artisan db:seed --class=RoleAndPermissionSeeder --force
```

---

## 3. Credenciales SMTP para correos en producción

Actualmente `MAIL_MAILER=log` (correos van al log, no se envían).

Para activar el envío real, configurar en el `.env` del servidor:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=crea@iyemyucatan.com
MAIL_PASSWORD=contraseña-del-correo
MAIL_FROM_ADDRESS=crea@iyemyucatan.com
MAIL_FROM_NAME="CREA IYEM Yucatán"
```

---

## 4. Aviso de privacidad

El formulario de registro de ciudadanos incluye un checkbox:
> "He leído y acepto el Aviso de Privacidad del programa CREA"

El link actual apunta a `/#aviso-privacidad`. Si se crea una página separada
de aviso de privacidad, actualizar el href en `CiudadanoRegister.vue`.

---

## 5. Verificación de correo electrónico (opcional)

Actualmente el registro de ciudadanos NO requiere verificar el correo.
Si se desea activar:
1. En `app/Models/User.php`, descomentar `implements MustVerifyEmail`
2. Esto activará automáticamente el envío del correo de verificación
   (plantilla ya personalizada en `resources/views/vendor/notifications/email.blade.php`)
3. Revisar la ruta `/verify-email` y la página `VerifyEmail.vue`

---

## 6. Migración pendiente en producción

Ejecutar en el servidor después del siguiente deploy:
```bash
php artisan migrate --force
```

Esto agrega las columnas `google_id` y `avatar` a la tabla `users`.
