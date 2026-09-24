# AUDITORÍA — Portal Ciudadano CREA (`/mi-portal`)
> Generado: 2026-06-11 | Stack: Laravel 11 + Vue 3 + Inertia.js + TypeScript + Tailwind CSS

---

## 1. Resumen Ejecutivo

El portal ciudadano tiene una base funcional sólida: layout propio, 4 vistas (Dashboard, Solicitud, Expediente, Mi Crédito), navegación en español, dark mode y responsividad. Sin embargo, se identificaron **2 bugs P0 críticos** que afectan correctitud legal y seguridad, **6 hallazgos P1** que impiden cumplir las Reglas de Operación o presentan riesgos de seguridad, y **10+ mejoras P2/P3** de UX/UI. El sistema de documentos requeridos está desconectado del modelo `ModalidadCrea` (que sí tiene los documentos correctos por modalidad), y los archivos subidos (INE, CURP) se almacenan en disco público con URLs predecibles. Estos dos problemas son los que deben resolverse primero.

---

## 2. Tabla de Hallazgos Priorizados

### P0 — Crítico (bloquea el flujo o representa riesgo de seguridad grave)

| # | Archivo(s) | Hallazgo | Propuesta | BD? |
|---|-----------|----------|-----------|-----|
| P0-1 | `SolicitudCreditoController.php:67,105`<br>`Solicitud.vue` | **Validación de monto desconectada de la modalidad.** El backend valida `min:100|max:1000000` sin importar qué modalidad eligió el usuario. Un ciudadano puede solicitar $3,000 para "Emprendedores" (mínimo $25,000) o $600,000 para "Artesanal" (máximo $25,000) y pasar validación. La BD en `modalidad_creas` ya tiene `monto_minimo` y `monto_maximo` con los valores correctos. | Regla custom de validación que consulta `ModalidadCrea::find($request->modalidad_id)->monto_minimo/maximo`. En Solicitud.vue, al seleccionar modalidad: ajustar dinámicamente `min`/`max` del input y mostrar el rango permitido. | No |
| P0-2 | `DocumentoSolicitud.php:35-42`<br>`SolicitudCreditoController.php:119,163`<br>`Solicitud.vue` | **El sistema de documentos ignora los requerimientos por modalidad.** `tiposRequeridos()` devuelve siempre los mismos 5 docs hardcodeados. Sin embargo, en la BD `modalidad_creas.documentos_requeridos` (JSON) ya define los docs correctos: Artesanal necesita `constancia_artesano`, Sustentable necesita `opinion_cumplimiento` + `plan_negocio`, Emprendedores necesita `opinion_cumplimiento` + `constancia_situacion`. Consecuencias: (a) ciudadano Artesanal puede "enviar" sin `constancia_artesano`; (b) si intenta subir `constancia_artesano`, el sistema lo rechaza como "tipo no permitido" (`in:` de la validación en `subirDocumento`). | Refactorizar `tiposRequeridos()` para recibir una `ModalidadCrea` y devolver sus `documentos_requeridos`. Actualizar `enviar()` y `subirDocumento()` para consultar los docs requeridos de la modalidad activa. Actualizar `Solicitud.vue` para mostrar solo los docs de la modalidad seleccionada. | No |
| P0-3 | `SolicitudCreditoController.php:128-136`<br>`DocumentoSolicitud.php:30` | **Documentos sensibles en disco público.** `subirDocumento()` usa `'public'` disk: los archivos quedan en `storage/app/public/solicitudes/{id}/ine_frente.jpg`. Con el symlink activo, cualquier persona puede acceder a `https://crea.iyemyucatan.com/storage/solicitudes/1/ine_frente.jpg`. INE, CURP y comprobantes de domicilio son datos sensibles bajo la LFPDPPP. | Cambiar el disk a `'local'` (privado). Crear una ruta autenticada `GET /mi-portal/solicitud/documento/{documento}` que valide que el documento pertenece al usuario, luego devuelva el archivo con `Storage::disk('local')->download(...)` o como `StreamedResponse`. Actualizar `getUrlAttribute` para usar esa ruta protegida. | No |

---

### P1 — Alto (datos faltantes según RO, seguridad, UX que bloquea el proceso)

| # | Archivo(s) | Hallazgo | Propuesta | BD? |
|---|-----------|----------|-----------|-----|
| P1-1 | `solicitudes_credito` (tabla)<br>`SolicitudCreditoController.php`<br>`Solicitud.vue` | **Falta el campo `plazo_meses` en la solicitud.** El ciudadano no puede proponer el plazo deseado (12/18/24 meses). El crédito tiene `plazo_meses` pero lo define el analista. Las RO (Art. 12) establecen que "el solicitante podrá proponer el número de cuotas, el cual podrá ser aprobado o modificado por el Comité". Este dato es usado para la tabla de amortización. | Migración: agregar `plazo_meses` INT nullable a `solicitudes_credito`. Agregar a `$fillable` y validación (`required|in:12,18,24`). Agregar select en `Solicitud.vue` Paso 2, junto al monto. | **Sí** |
| P1-2 | `SolicitudCreditoController.php`<br>`Solicitud.vue`<br>Falta tabla nueva | **Sin captura de datos del aval/garantía personal.** Las RO exigen que el solicitante presente un aval con domicilio en Yucatán, que no sea familiar de primer o segundo grado. No existe ningún campo ni tabla para datos del aval, ni documentos relacionados (INE del aval, declaración patrimonial). | Agregar sección "Datos del Aval" al formulario con: nombre_aval, curp_aval, municipio_aval, parentesco_aval, telefono_aval. Agregar documentos `ine_aval_frente`, `ine_aval_reverso`, `declaracion_patrimonial_aval` al set de requeridos (al menos para Emprendedores y Sustentable). Puede ser como campos en `solicitudes_credito` o tabla separada `avales_solicitud`. | **Sí** |
| P1-3 | `BeneficiarioController.php:43-57`<br>`Dashboard.vue` | **`motivo_rechazo` no llega al Dashboard.** El Dashboard mapea manualmente los campos y no incluye `motivo_rechazo`. Si un ciudadano ve el Dashboard (no la vista de Solicitud) cuando su solicitud fue rechazada, no sabrá por qué. En `Solicitud.vue` sí aparece porque usa `$solicitud->toArray()`. | Agregar `'motivo_rechazo' => $solicitud->motivo_rechazo` al array del Dashboard en `BeneficiarioController.php:43-57`. Actualizar `Dashboard.vue` para mostrar el motivo en el card de estado "Rechazada". | No |
| P1-4 | `MiCreditoController.php`<br>`MiCredito.vue` | **Sin próxima cuota destacada ni saldo insoluto actual.** El ciudadano debe buscar en una tabla de 12-24 filas para saber cuándo y cuánto pagar. No hay un card prominente con "Próxima cuota: $X — Vence el DD/MM/YYYY". Tampoco se muestra el saldo total pendiente calculado. | En `MiCreditoController`: calcular `proxima_cuota` (primera amortización con estado `Pendiente`/`Parcial` ordenada por numero_cuota) y `saldo_insoluto_total` (suma de `pago_restante` de cuotas no pagadas). Pasar como props adicionales y mostrar como card principal en `MiCredito.vue`. | No |
| P1-5 | `MiCredito.vue` | **Crédito `Moroso` sin UI de alerta ni guía para regularizarse.** Solo hay un badge de color rojo en el estatus. Un ciudadano moroso necesita saber: (a) días de mora, (b) monto de mora acumulada, (c) teléfono/instrucciones para regularizarse. El modelo `Credito` ya tiene `diasMora()` y `semaforoRiesgo()`. | Agregar en `MiCreditoController` los campos `dias_mora` y `mora_total` cuando `credito.estatus === 'Moroso'`. En `MiCredito.vue` mostrar un banner de alerta (no alarmista) con los datos de mora y el número de contacto para pago. | No |
| P1-6 | `ExpedienteController.php`<br>`Expediente.vue` | **El expediente no incluye documentos generados por el sistema** (contrato PDF, dictamen, tabla de amortización) cuando el crédito ya fue aprobado. El modelo `Credito` tiene `contrato_ruta`. El ciudadano no tiene acceso directo a descargar su contrato. | Cuando `solicitud.credito_id` existe, agregar al payload del `ExpedienteController` los documentos del crédito: link al contrato, link al estado de cuenta PDF. Evaluar crear ruta `portal.credito.contrato` protegida que sirva el archivo `contrato_ruta`. | No |

---

### P2 — Medio (mejoras de UX/UI, claridad, consistencia visual)

| # | Archivo(s) | Hallazgo | Propuesta | BD? |
|---|-----------|----------|-----------|-----|
| P2-1 | `Solicitud.vue` | **Sin stepper de proceso post-envío.** El ciudadano que ya envió su solicitud ve un card de estatus, pero no un timeline visual (Borrador → Enviada → En Revisión → Aprobada/Rechazada) que indique claramente en qué etapa está y qué sigue. | Agregar componente `SolicitudTimeline.vue` con 4-5 pasos visuales, resaltando el actual. Similar al stepper del formulario pero horizontal y sobre el card de estatus. | No |
| P2-2 | `Solicitud.vue:98-101` | **Municipios hardcodeados en el frontend** (20 valores fijos). No está alineado con `modalidad_creas.municipios_elegibles` (actualmente null = todos). Si cambias los municipios elegibles desde la BD, el frontend no se actualiza. | Pasar la lista de municipios desde `SolicitudCreditoController::index()` como prop (obtenida de BD/config), o al menos centralizar en un archivo de constantes TypeScript. | No |
| P2-3 | `SolicitudCreditoController.php:22`<br>`Solicitud.vue` | **Las `modalidades` prop no incluye `monto_minimo`, `monto_maximo`, `plazo_min_meses`, `plazo_max_meses`**. El select de modalidad en el form solo muestra nombre y tasa, sin ayudar al ciudadano a elegir. | Agregar esos campos al query `ModalidadCrea::all(['id', 'nombre', 'tasa_interes', 'monto_minimo', 'monto_maximo', 'plazo_min_meses', 'plazo_max_meses'])`. En el frontend: al seleccionar modalidad, mostrar un card informativo con el rango de monto y plazos disponibles. | No |
| P2-4 | `Solicitud.vue`<br>`MiCredito.vue` | **Modalidad Sustentable: prórroga de 3 meses no comunicada al ciudadano.** No hay ningún texto en el portal que explique que la modalidad Sustentable incluye 3 meses de prórroga para el primer pago (aunque los intereses se siguen generando). Un ciudadano podría malentender que "no le cobran" esos primeros 3 meses. | Agregar nota informativa en el card de modalidad Sustentable en `Solicitud.vue` y en el resumen de `MiCredito.vue` si la modalidad es Sustentable. Texto sugerido: "Esta modalidad incluye 3 meses de prórroga antes del primer pago. Durante ese periodo se generan intereses." | No |
| P2-5 | `Expediente.vue`<br>`Solicitud.vue` | **Duplicación confusa: documentos aparecen en dos vistas.** `Expediente.vue` y la sección de documentos de `Solicitud.vue` muestran los mismos archivos. El primero es read-only, el segundo permite subir. Para un ciudadano sin experiencia técnica, no queda claro si "Mi Expediente" es donde gestiona sus docs o solo donde los consulta. | Hacer que `Expediente.vue` sea la única fuente de verdad para ver documentos (read-only). Que `Solicitud.vue` solo muestre el estado de cada doc con un botón "Gestionar en Mi Expediente →". Opcionalmente: unificar en una sola vista con modo lectura/edición según estatus. | No |
| P2-6 | `BeneficiarioLayout.vue:28-33` | **Nav siempre muestra "Solicitar Crédito"** aunque el ciudadano ya tenga solicitud activa o crédito. El `BeneficiarioController` previene duplicados pero la UX invita al error. | Cambiar el nav item "Solicitar Crédito" a "Mi Solicitud" y mostrar siempre (redirige a `portal.solicitud.index` que maneja el estado). Ya existe la lógica en el layout para `tieneCredito`; agregar `tieneSolicitud` a los props del layout para ajustar el label. | No |
| P2-7 | `MiCredito.vue:242` | **Liquidación anticipada: el modal no explica el beneficio en lenguaje ciudadano.** Solo muestra los montos sin contextar que "al liquidar anticipadamente ahorras X de intereses futuros". | Calcular y mostrar `ahorro_intereses = totalInteresesFuturos` en el modal. Agregar texto: "Al liquidar hoy, ahorras $X en intereses futuros que no se generarán." | No |
| P2-8 | `routes/web.php` | **Sin rate limiting en endpoints de subida de documentos y envío.** `portal.solicitud.documento` y `portal.solicitud.enviar` no tienen throttle. Podría generarse carga excesiva por resubidas masivas o ataques automatizados. | Agregar `->middleware(['throttle:10,1'])` a `portal.solicitud.documento` (10 requests por minuto) y `->middleware(['throttle:5,1'])` a `portal.solicitud.enviar`. | No |
| P2-9 | `BeneficiarioController.php:60-64` | **`marcarLeido` permite a cualquier ciudadano marcar como leído un anuncio global** (con `user_id = null`). Si el anuncio global tiene un único registro y un ciudadano lo marca, ¿afecta a todos los demás? Depende de la implementación del scope `paraUsuario`. | Revisar el scope `AnuncioCiudadano::paraUsuario()`. Si los anuncios globales son registros únicos compartidos, la lógica de `leido` debe ser per-usuario (tabla pivot `anuncios_leidos`). Si hay una copia por usuario, el scope ya maneja correctamente. | Posible |
| P2-10 | `Solicitud.vue:103` | **Inconsistencia visual: `focus:ring-red-500`** en lugar de `focus:ring-[#6B1938]` (guinda) como en las páginas de Auth. | Reemplazar `focus:ring-red-500/10 focus:border-red-600` por `focus:ring-[#6B1938]/10 focus:border-[#6B1938]` en `inputClass`. | No |

---

### P3 — Bajo (pulido, microdetalles, copywriting)

| # | Archivo(s) | Hallazgo | Propuesta | BD? |
|---|-----------|----------|-----------|-----|
| P3-1 | `MiCredito.vue:242`<br>`BeneficiarioLayout.vue:221`<br>`Solicitud.vue:157` | Teléfono "999 941 2170" hardcodeado en 5+ lugares | Centralizar en `resources/js/config/contacto.ts` como constante exportada | No |
| P3-2 | `DocumentoSolicitud::tiposRequeridos()` | Label "Fotografía del Negocio o Proyecto" es ambiguo para modalidad Artesanal | Cuando se refactorice para docs por modalidad, usar labels específicos por tipo ("Fotografía de tu taller artesanal", "Evidencia de tu proyecto sustentable") | No |
| P3-3 | `Expediente.vue` | Campo "Alta SAT" aparece como badge "No" en color neutro aunque es requisito para Emprendedores/Sustentable. No sugiere acción | Si la modalidad requiere alta SAT y el campo es `false`, mostrar en amber con texto "Requerida para esta modalidad" | No |
| P3-4 | `Dashboard.vue` | El Dashboard no muestra el monto solicitado ni el plazo propuesto (cuando se implemente P1-1) en el resumen de la solicitud | Agregar `monto_solicitado` y `plazo_meses` al payload del `BeneficiarioController` y al card de resumen en Dashboard | No |
| P3-5 | Todas las vistas del portal | Sin breadcrumbs. La navegación activa está resaltada pero falta el contexto de "dónde estoy" especialmente en Expediente y MiCredito | Agregar breadcrumb simple (ej. "Portal › Mi Expediente") debajo del nav en cada página | No |

---

## 3. Modalidades: Flujo de Captura, Validación y Montos

### Estado actual vs. estado deseado

| Campo | Artesanal | Emprendedores | Sustentable | Estado actual |
|-------|-----------|---------------|-------------|---------------|
| Monto | $5,000 – $25,000 | $25,000 – $150,000 | $50,000 – $500,000 | ❌ Valida min:100 max:1000000 sin checar modalidad |
| Tasa ordinaria | 0% | 7% | 5% | ✅ Correcto en BD |
| Plazos | 12, 18, 24 meses | 12, 18, 24 meses | 12, 18, 24 meses | ❌ No se captura en la solicitud |
| Alta SAT requerida | ❌ No | ✅ Sí | ✅ Sí | ⚠️ Capturado como checkbox informativo, no validado condicionalmente |
| Prórroga primer pago | — | — | 3 meses | ❌ No informado al ciudadano |
| Tasa moratoria | 0% | 17.5% | 12.5% | ✅ Correcto en BD |

### Flujo de captura recomendado en `Solicitud.vue` (Paso 2 — Datos del Negocio)

```
[Select: Modalidad de crédito]
  → Al seleccionar: mostrar card informativo:
    - Rango de monto: $X,000 – $X,000
    - Plazos disponibles: 12, 18, 24 meses
    - Tasa de interés: X%
    - Si Sustentable: "Incluye 3 meses de prórroga para el primer pago"
    - Si Emprendedores/Sustentable: "Requiere estar dado de alta en el SAT"

[Input: Monto solicitado] ← min/max dinámicos según modalidad
[Select: Plazo deseado (meses)] ← 12 | 18 | 24 (siempre los 3 para las 3 modalidades)
```

### Validación backend recomendada

```php
// En store() y update() de SolicitudCreditoController
$modalidad = ModalidadCrea::find($request->modalidad_id);

$data = $request->validate([
    // ... otros campos ...
    'monto_solicitado' => [
        'nullable', 'numeric',
        "min:{$modalidad->monto_minimo}",
        "max:{$modalidad->monto_maximo}",
    ],
    'plazo_meses' => 'required|in:12,18,24',
    'alta_sat' => [
        'boolean',
        $modalidad->requiere_alta_sat ? 'accepted' : '', // obliga el checkbox si la modalidad lo requiere
    ],
]);
```

---

## 4. Documentos Requeridos: RO vs. Implementación Actual

### Documentos actuales (`DocumentoSolicitud::tiposRequeridos()`)

Los mismos 5 para todas las modalidades:
- `ine_frente` — INE / Credencial (Frente)
- `ine_reverso` — INE / Credencial (Reverso)
- `curp` — Documento CURP oficial
- `comprobante_domicilio` — Comprobante de Domicilio
- `foto_negocio` — Fotografía del Negocio o Proyecto

### Documentos en la BD por modalidad (`modalidad_creas.documentos_requeridos`)

| Documento | Artesanal | Emprendedores | Sustentable |
|-----------|:---------:|:-------------:|:-----------:|
| ine_frente | ✅ | ✅ | ✅ |
| ine_reverso | ✅ | ✅ | ✅ |
| curp | ✅ | ✅ | ✅ |
| comprobante_domicilio | ✅ | ✅ | ✅ |
| foto_negocio | ✅ | ✅ | ✅ |
| constancia_artesano | ✅ | ❌ | ❌ |
| opinion_cumplimiento | ❌ | ✅ | ✅ |
| constancia_situacion | ❌ | ✅ | ❌ |
| plan_negocio | ❌ | ❌ | ✅ |

### Documentos adicionales según RO (no en el sistema todavía)

Estos documentos son mencionados en las Reglas de Operación (Arts. 9-10) y NO están en la BD ni en el código:

| Documento | Modalidad | Prioridad |
|-----------|-----------|-----------|
| Acta de nacimiento | Todas (ciudadanos) | P1 |
| Documento que acredite propiedad/posesión del local | Emprendedores, Sustentable | P1 |
| Cotizaciones del proyecto (mínimo 2) | Todas | P1 |
| INE del aval (frente + reverso) | Todas | P1 |
| Declaración de bienes patrimoniales del aval | Todas | P1 |
| Evidencia de productos muestra | Artesanal | P2 |
| Relación de ingresos/egresos 6 meses + proyección | Emprendedores, Sustentable | P2 |

### Propuesta de implementación

**Opción A (recomendada — menor complejidad):** Enriquecer `modalidad_creas.documentos_requeridos` con los tipos faltantes y refactorizar `tiposRequeridos()` para leer de la BD.

```php
// DocumentoSolicitud.php
public static function tiposRequeridos(?ModalidadCrea $modalidad = null): array
{
    $base = [
        'ine_frente'            => 'INE vigente (Frente)',
        'ine_reverso'           => 'INE vigente (Reverso)',
        'curp'                  => 'CURP (documento oficial)',
        'comprobante_domicilio' => 'Comprobante de domicilio (máx. 3 meses)',
        'foto_negocio'          => 'Fotografía del negocio o proyecto',
    ];

    if (!$modalidad) return $base;

    $extra = collect($modalidad->documentos_requeridos ?? [])
        ->except(array_keys($base))
        ->toArray();

    return array_merge($base, $extra);
}
```

**Opción B:** Tabla `catalogo_documentos` con relación muchos-a-muchos a `modalidad_creas`, con campo `obligatorio` y `descripcion`. Más flexible pero requiere migración y seeder.

---

## 5. Seguridad: Scoping de Datos por Usuario

### Estado actual

| Controlador | Método de scoping | Riesgo |
|-------------|-------------------|--------|
| `BeneficiarioController` | `auth()->user()->solicitudCredito()` | ✅ Correcto |
| `SolicitudCreditoController` | `auth()->user()->solicitudCredito` | ✅ Correcto |
| `ExpedienteController` | `auth()->user()->solicitudCredito()` | ✅ Correcto |
| `MiCreditoController` | `$user->solicitudCredito` → `findOrFail($credito_id)` | ⚠️ Ver nota |
| `EstadoCuentaController` | Método privado `getCreditoUsuario()` | ✅ Correcto |
| Archivos en disco | `Storage::disk('public')` — URLs predecibles | ❌ **P0-3** |

**Nota MiCreditoController:** El scoping es correcto porque `credito_id` viene del registro de la solicitud del usuario autenticado. Sin embargo, si un operador asigna incorrectamente un `credito_id` a una solicitud de otro usuario, el ciudadano vería un crédito ajeno. Se recomienda agregar una verificación adicional: `abort_if($credito->acreditado_id !== $solicitud->acreditado_id, 403)`.

---

## 6. Recomendación de Siguiente Paso

### Iteración 1 — Críticos y legales (aprox. 1 día de desarrollo)
1. **P0-2**: Refactorizar documentos por modalidad (conectar `tiposRequeridos()` con la BD)
2. **P0-1**: Agregar validación de monto según modalidad (backend + frontend dinámico)
3. **P1-1**: Migración + campo `plazo_meses` en solicitud + select en el form (12/18/24)
4. **P2-3**: Enriquecer props de modalidades con `monto_minimo`/`maximo`/`plazo_*`

### Iteración 2 — Seguridad de archivos (aprox. 0.5 días)
5. **P0-3**: Migrar a disco privado + ruta autenticada para servir documentos

### Iteración 3 — Completitud según RO (aprox. 1 día)
6. **P1-2**: Captura de datos del aval (campos + documentos)
7. **P2-4**: Info de prórroga Sustentable
8. **P1-3**: `motivo_rechazo` en Dashboard

### Iteración 4 — UX/Claridad (aprox. 1 día)
9. **P1-4 + P1-5**: Próxima cuota destacada + alerta de mora en MiCredito
10. **P2-1**: Stepper visual de proceso
11. **P2-2 + P2-6**: Municipios desde BD, nav "Mi Solicitud"
12. **P1-6**: Contrato PDF accesible desde Expediente
