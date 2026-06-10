# CHANGELOG DE AUDITORÍA — SISTEMA CREA
# Fecha: 2026-06-09

---

## P0 — CRÍTICO (corregidos)

### [2026-06-09] [P0] [PagoController.php:68] Fecha de pago permitía fechas futuras
**Cambio:** `'fecha_pago' => 'required|date'` → `'required|date|before_or_equal:today'`
**Por qué:** Un pago con fecha futura distorsiona el cálculo de mora y el historial financiero.

### [2026-06-09] [P0] [PagoController.php:69] Forma de pago sin whitelist
**Cambio:** `'forma_pago' => 'required|string'` → `'required|in:Efectivo,Transferencia,Cheque,Tarjeta'`
**Por qué:** Acepta cualquier string sin validación; debe limitarse a los valores del catálogo.

### [2026-06-09] [P0] [PagoController.php:cancelar()] Cancelación no recalculaba estatus Moroso
**Cambio:** Al cancelar un pago que había liquidado el crédito, solo se fijaba 'Activo' sin revisar si había cuotas vencidas. Ahora se recalcula correctamente (Liquidado / Moroso / Activo).
**Por qué:** Un crédito podría quedar en 'Activo' teniendo cuotas vencidas tras una cancelación.

### [2026-06-09] [P0] [PagoController.php:aplicarAbonoCapital()] Abono anticipado "Reducir Cuota" reseteaba capital_pagado a 0
**Cambio:** Eliminadas las líneas que sobrescribían `capital_pagado` e `interes_ordinario_pagado` a 0 al recalcular la tabla.
**Por qué:** Si una cuota tenía pagos parciales antes del abono anticipado, se perdían esos pagos en el recálculo.

### [2026-06-09] [P0] [UpdateMoratorio.php] `moratorio_acumulado` nunca se sincronizaba
**Cambio:** El comando daily ahora actualiza también `moratorio_acumulado` junto con `interes_moratorio_generado`.
**Por qué:** El portal ciudadano (MiCreditoController, EstadoCuentaController) lee `moratorio_acumulado` que siempre era 0, mostrando $0 de mora aunque el crédito estuviera vencido.

### [2026-06-09] [P0] [ReestructuracionController.php:store()] Reestructuración eliminaba amortizaciones permanentemente
**Cambio:** `->delete()` cambiado a `->update(['estado' => 'Reestructurada', 'pago_restante' => 0])`.
**Migración:** `2026_06_09_000001_add_reestructurada_to_amortizaciones_estado_enum.php` agrega el valor al ENUM.
**Por qué:** Los registros de amortizaciones con pagos parciales se perdían irrecuperablemente; viola auditoría financiera.

### [2026-06-09] [P0] [Múltiples controllers] Cuotas 'Reestructurada' incluidas en cálculos activos
**Cambio:** Todos los filtros `where('estado', '!=', 'Pagado')` y `whereNotIn(['Pagado','Condonado'])` actualizados a `whereNotIn(['Pagado','Condonado','Reestructurada'])`.
**Archivos afectados:** PagoController, UpdateMoratorio, RecordatoriosPago, CreditoController, AcreditadoController, CondonacionFormalController, DashboardController, ReporteController, Portal/EstadoCuentaController, Credito.php (model).
**Por qué:** Sin este cambio, las cuotas antiguas reestructuradas seguirían apareciendo como activas, inflando saldos y recordatorios.

---

## P1 — ALTO (corregidos)

### [2026-06-09] [P1] [DesembolsoController.php:43] Monto desembolsado sin límite máximo
**Cambio:** Validación cambiada a `['required','numeric','min:0.01','max:' . $credito->monto_otorgado]`.
**Por qué:** Sin límite, era posible desembolsar montos superiores al crédito otorgado.

### [2026-06-09] [P1] [DashboardTest.php:11] Test no asignaba tipo=operativo al usuario
**Cambio:** `User::factory()->create()` → `User::factory()->create(['tipo' => 'operativo'])`.
**Por qué:** El middleware EsOperativo redirigía al usuario de prueba; el test estaba roto desde antes.

---

## P1 — ALTO (debug code eliminado)

### [2026-06-09] [P1] [Acreditados/Create.vue] console.log de debug en producción
**Cambio:** Eliminados console.log en submit() que exponían datos internos en consola del navegador.

### [2026-06-09] [P1] [Interesados/Index.vue] console.log de debug en producción
**Cambio:** Eliminados console.log en convertirInteresado() con emojis de debug.

---

## Hallazgos pendientes (documentados, no cambiados)

### [P2] DashboardController usa DATEDIFF() (función MySQL)
Los tests fallan en SQLite por uso de DATEDIFF en queries raw. En producción MySQL funciona correctamente. Recomendado: abstraer a una query Eloquent compatible o añadir skip de SQLite en el test.

### [P2] CreditService.php calcula mora sobre pago_restante (no sobre capital vencido)
La clase CreditService calcula mora sobre `pago_restante` en lugar de `capital_esperado - capital_pagado`. Inconsistente con PagoController y UpdateMoratorio. Esta clase no parece llamarse activamente; el cálculo real de mora está en PagoController y UpdateMoratorio.

### [P3] Índices faltantes en amortizaciones y pagos
Recomendado agregar: `(credito_id, estado, fecha_vencimiento)` en amortizaciones y `(credito_id, cancelado)` en pagos.

### [P3] Documentos de desembolso se almacenan en disco público
`DesembolsoController::store()` usa `store('desembolsos', 'public')`. Los comprobantes son accesibles por URL directa. Recomendado migrar a disco privado con URLs firmadas.

### [P3] RFC/CURP sin validación de formato
Validaciones aceptan cualquier string; recomendado agregar regex de formato RFC y CURP.

---

---

## Fix post-deploy (2026-06-10)

### [2026-06-10] [P0] [Hostinger] 419 Page Expired en login y forgot-password
**Causa raíz:** Apache en Hostinger shared hosting NO sigue symlinks en `mod_rewrite` `RewriteCond %{REQUEST_FILENAME} !-f`. El symlink `build/ -> public/build/` no era reconocido como directorio real, por lo que todas las solicitudes a `/build/assets/*.js` se enrutaban a `index.php` (Laravel → 404). Sin el JS de Inertia, el browser enviaba el formulario como HTML puro sin `X-XSRF-TOKEN` header → Laravel devolvía 419.
**Solución:** Reemplazar el symlink con copia real: `rm -f build && cp -r public/build/ build/`. Actualizado `deploy.sh` para hacer esto automáticamente en cada deploy.
**Verificado:** POST /login → HTTP 422 (CSRF pasa, credenciales incorrectas). POST /forgot-password → HTTP 302 (éxito).

---

## Resultado final

- `npm run build` ✅ Sin errores
- `php artisan test`: 26/27 tests pasan. 1 fallo pre-existente: DashboardTest por DATEDIFF en SQLite (no afecta producción MySQL).
