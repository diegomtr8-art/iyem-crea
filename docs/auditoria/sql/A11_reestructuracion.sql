-- 1. CREDITOS
SELECT c.id AS credito_id, c.clave_contrato, m.nombre AS modalidad, c.monto_otorgado, c.plazo_meses,
       c.tasa_interes_ordinario, c.tasa_interes_moratorio, c.fecha_entrega, c.estatus
FROM creditos c
JOIN modalidad_creas m ON m.id = c.modalidad_id
WHERE c.id IN (2, 5, 22, 29, 30, 43)
ORDER BY c.id;

-- 2. AMORTIZACIONES (ANTES Y DESPUES)
SELECT id, credito_id, numero_cuota, fecha_vencimiento, saldo_insoluto, capital_esperado,
       interes_ordinario_esperado, cuota_fija, capital_pagado, interes_ordinario_pagado,
       interes_moratorio_pagado, interes_moratorio_generado, pago_restante, estado
FROM amortizaciones
WHERE credito_id IN (2, 5, 22, 29, 30, 43)
ORDER BY credito_id, numero_cuota;

-- 3. REESTRUCTURACIONES
SELECT id, credito_id, fecha_reestructura, motivo, saldo_al_momento, mora_condonada, interes_condonado,
       nuevo_plazo_meses, nueva_tasa_interes, nueva_fecha_inicio_pagos, numero_resolutivo
FROM reestructuraciones
ORDER BY id;

-- 4. PRIMERA CUOTA NUEVA CONTRA INICIO DE PAGOS
SELECT r.credito_id, r.nueva_fecha_inicio_pagos, MIN(a.fecha_vencimiento) AS primera_vence,
       DATEDIFF(MIN(a.fecha_vencimiento), r.nueva_fecha_inicio_pagos) AS dias_desfase
FROM reestructuraciones r
JOIN amortizaciones a ON a.credito_id = r.credito_id AND a.estado <> 'Reestructurada'
     AND a.created_at >= r.created_at
GROUP BY r.id, r.credito_id, r.nueva_fecha_inicio_pagos
ORDER BY r.credito_id;

-- 5. CAPITAL DE LA TABLA NUEVA CONTRA BASE
SELECT r.credito_id, r.saldo_al_momento, SUM(a.capital_esperado) AS suma_capital,
       SUM(a.capital_esperado) - r.saldo_al_momento AS diferencia
FROM reestructuraciones r
JOIN amortizaciones a ON a.credito_id = r.credito_id AND a.estado <> 'Reestructurada'
     AND a.created_at >= r.created_at
GROUP BY r.id, r.credito_id, r.saldo_al_momento
ORDER BY r.credito_id;

-- 6. CUOTAS VIEJAS REESTRUCTURADAS CON SALDO
SELECT credito_id, COUNT(*) AS reestructuradas, SUM(pago_restante <> 0) AS con_pago_restante
FROM amortizaciones
WHERE estado = 'Reestructurada'
GROUP BY credito_id
ORDER BY credito_id;
