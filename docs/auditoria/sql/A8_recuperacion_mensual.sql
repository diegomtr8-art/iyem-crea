-- 0. PARAMETROS
SET @hasta = CURDATE();
SET @fin   = CAST(DATE_FORMAT(@hasta, '%Y-%m-01') AS DATE) + INTERVAL 1 MONTH;
SET @desde = @fin - INTERVAL 24 MONTH;



-- 1. RECUPERACION MENSUAL
WITH RECURSIVE meses AS (
    SELECT @desde AS inicio
    UNION ALL
    SELECT inicio + INTERVAL 1 MONTH FROM meses WHERE inicio + INTERVAL 1 MONTH < @fin
),
p AS (
    SELECT
        DATE_FORMAT(fecha_pago, '%Y-%m') AS mes,
        SUM(monto_recibido) AS total_recibido,
        SUM(aplicado_capital) AS aplicado_capital,
        SUM(aplicado_ordinario) AS aplicado_ordinario,
        SUM(aplicado_mora) AS aplicado_mora,
        SUM(monto_recibido - aplicado_capital - aplicado_ordinario - aplicado_mora) AS sin_aplicar,
        COUNT(*) AS num_pagos,
        COUNT(DISTINCT acreditado_id) AS acreditados_que_pagaron
    FROM pagos
    WHERE cancelado = 0
      AND fecha_pago >= @desde
      AND fecha_pago <  @fin
    GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
),
e AS (
    SELECT
        DATE_FORMAT(a.fecha_vencimiento, '%Y-%m') AS mes,
        SUM(a.cuota_fija) AS esperado
    FROM amortizaciones a
    JOIN creditos c ON c.id = a.credito_id
    WHERE a.fecha_vencimiento >= @desde
      AND a.fecha_vencimiento <  @fin
      AND c.estatus <> 'Cancelado'
      AND (
            a.estado <> 'Reestructurada'
         OR a.fecha_vencimiento < (
                SELECT MIN(r.fecha_reestructura)
                FROM reestructuraciones r
                WHERE r.credito_id = a.credito_id
                  AND r.created_at >= a.created_at
            )
      )
    GROUP BY DATE_FORMAT(a.fecha_vencimiento, '%Y-%m')
)
SELECT
    DATE_FORMAT(m.inicio, '%Y-%m') AS mes,
    COALESCE(p.total_recibido, 0) AS total_recibido,
    COALESCE(p.aplicado_capital, 0) AS aplicado_capital,
    COALESCE(p.aplicado_ordinario, 0) AS aplicado_ordinario,
    COALESCE(p.aplicado_mora, 0) AS aplicado_mora,
    COALESCE(p.sin_aplicar, 0) AS sin_aplicar,
    COALESCE(p.num_pagos, 0) AS num_pagos,
    COALESCE(p.acreditados_que_pagaron, 0) AS acreditados_que_pagaron,
    COALESCE(e.esperado, 0) AS esperado,
    ROUND(100 * COALESCE(p.total_recibido, 0) / NULLIF(e.esperado, 0), 2) AS porcentaje_recuperacion
FROM meses m
LEFT JOIN p ON p.mes = DATE_FORMAT(m.inicio, '%Y-%m')
LEFT JOIN e ON e.mes = DATE_FORMAT(m.inicio, '%Y-%m')
ORDER BY m.inicio;



-- 2. POR MODALIDAD
SELECT
    mo.id AS modalidad_id,
    mo.nombre AS modalidad,
    COALESCE(p.total_recibido, 0) AS total_recibido,
    COALESCE(p.aplicado_capital, 0) AS aplicado_capital,
    COALESCE(p.aplicado_ordinario, 0) AS aplicado_ordinario,
    COALESCE(p.aplicado_mora, 0) AS aplicado_mora,
    COALESCE(p.sin_aplicar, 0) AS sin_aplicar,
    COALESCE(p.num_pagos, 0) AS num_pagos,
    COALESCE(p.acreditados_que_pagaron, 0) AS acreditados_que_pagaron,
    COALESCE(e.esperado, 0) AS esperado,
    ROUND(100 * COALESCE(p.total_recibido, 0) / NULLIF(e.esperado, 0), 2) AS porcentaje_recuperacion,
    ROUND(100 * COALESCE(p.total_recibido, 0) / SUM(COALESCE(p.total_recibido, 0)) OVER (), 2) AS porcentaje_del_total
FROM modalidad_creas mo
LEFT JOIN (
    SELECT
        c.modalidad_id,
        SUM(pg.monto_recibido) AS total_recibido,
        SUM(pg.aplicado_capital) AS aplicado_capital,
        SUM(pg.aplicado_ordinario) AS aplicado_ordinario,
        SUM(pg.aplicado_mora) AS aplicado_mora,
        SUM(pg.monto_recibido - pg.aplicado_capital - pg.aplicado_ordinario - pg.aplicado_mora) AS sin_aplicar,
        COUNT(*) AS num_pagos,
        COUNT(DISTINCT pg.acreditado_id) AS acreditados_que_pagaron
    FROM pagos pg
    JOIN creditos c ON c.id = pg.credito_id
    WHERE pg.cancelado = 0
      AND pg.fecha_pago >= @desde
      AND pg.fecha_pago <  @fin
    GROUP BY c.modalidad_id
) p ON p.modalidad_id = mo.id
LEFT JOIN (
    SELECT
        c.modalidad_id,
        SUM(a.cuota_fija) AS esperado
    FROM amortizaciones a
    JOIN creditos c ON c.id = a.credito_id
    WHERE a.fecha_vencimiento >= @desde
      AND a.fecha_vencimiento <  @fin
      AND c.estatus <> 'Cancelado'
      AND (
            a.estado <> 'Reestructurada'
         OR a.fecha_vencimiento < (
                SELECT MIN(r.fecha_reestructura)
                FROM reestructuraciones r
                WHERE r.credito_id = a.credito_id
                  AND r.created_at >= a.created_at
            )
      )
    GROUP BY c.modalidad_id
) e ON e.modalidad_id = mo.id
ORDER BY mo.id;



-- 3. CUADRE CONTRA EL TOTAL HISTORICO
SELECT
    SUM(CASE WHEN fecha_pago >= @desde AND fecha_pago < @fin THEN monto_recibido ELSE 0 END) AS recibido_24_meses,
    SUM(CASE WHEN fecha_pago <  @desde THEN monto_recibido ELSE 0 END) AS recibido_antes,
    SUM(CASE WHEN fecha_pago >= @fin   THEN monto_recibido ELSE 0 END) AS recibido_fecha_futura,
    SUM(monto_recibido) AS recibido_historico,
    COUNT(*) AS pagos_historico
FROM pagos
WHERE cancelado = 0;



-- 4. UN MES A MANO
SET @mes = '2026-08';

SELECT
    id, folio, credito_id, acreditado_id, fecha_pago, created_at,
    monto_recibido, aplicado_capital, aplicado_ordinario, aplicado_mora
FROM pagos
WHERE cancelado = 0
  AND DATE_FORMAT(fecha_pago, '%Y-%m') = @mes
ORDER BY fecha_pago, id;

SELECT a.credito_id, a.numero_cuota, a.fecha_vencimiento, a.estado, a.cuota_fija
FROM amortizaciones a
WHERE DATE_FORMAT(a.fecha_vencimiento, '%Y-%m') = @mes
ORDER BY a.fecha_vencimiento, a.credito_id;



-- 5. PAGOS DESCUADRADOS
SELECT COUNT(*)
FROM pagos
WHERE ABS(monto_recibido - (aplicado_capital + aplicado_ordinario + aplicado_mora)) > 0.01;

SELECT
    p.id, p.folio, p.credito_id, c.clave_contrato, c.estatus, p.fecha_pago, p.cancelado,
    p.monto_recibido,
    p.aplicado_capital + p.aplicado_ordinario + p.aplicado_mora AS suma_aplicados,
    p.monto_recibido - (p.aplicado_capital + p.aplicado_ordinario + p.aplicado_mora) AS diferencia,
    p.tipo_abono, p.sobrante_aplicado, p.observaciones
FROM pagos p
JOIN creditos c ON c.id = p.credito_id
WHERE ABS(p.monto_recibido - (p.aplicado_capital + p.aplicado_ordinario + p.aplicado_mora)) > 0.01
ORDER BY p.fecha_pago, p.id;



-- 6. FECHA DE PAGO CONTRA FECHA DE CAPTURA
SELECT
    COUNT(*) AS pagos,
    SUM(DATE_FORMAT(fecha_pago, '%Y-%m') <> DATE_FORMAT(created_at, '%Y-%m')) AS mes_distinto
FROM pagos
WHERE cancelado = 0;



-- 7. CANCELADOS
SELECT COUNT(*) AS pagos_cancelados, COALESCE(SUM(monto_recibido), 0) AS monto_cancelado
FROM pagos
WHERE cancelado = 1;



-- 8. CUOTAS REESTRUCTURADAS EN EL ESPERADO
SELECT
    SUM(CASE WHEN a.fecha_vencimiento <  r.primera THEN 1 ELSE 0 END) AS vencidas_antes_cuentan,
    SUM(CASE WHEN a.fecha_vencimiento <  r.primera THEN a.cuota_fija ELSE 0 END) AS monto_cuentan,
    SUM(CASE WHEN a.fecha_vencimiento >= r.primera OR r.primera IS NULL THEN 1 ELSE 0 END) AS sustituidas_no_cuentan,
    SUM(CASE WHEN a.fecha_vencimiento >= r.primera OR r.primera IS NULL THEN a.cuota_fija ELSE 0 END) AS monto_no_cuentan
FROM amortizaciones a
LEFT JOIN (
    SELECT a2.id, MIN(r2.fecha_reestructura) AS primera
    FROM amortizaciones a2
    JOIN reestructuraciones r2 ON r2.credito_id = a2.credito_id AND r2.created_at >= a2.created_at
    WHERE a2.estado = 'Reestructurada'
    GROUP BY a2.id
) r ON r.id = a.id
WHERE a.estado = 'Reestructurada'
  AND a.fecha_vencimiento >= @desde
  AND a.fecha_vencimiento <  @fin;
