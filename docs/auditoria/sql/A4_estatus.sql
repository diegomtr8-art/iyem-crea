
-- 1. ESTATUS GUARDADO
SELECT estatus, COUNT(*) AS creditos
FROM creditos
GROUP BY estatus
ORDER BY creditos DESC;



-- 2. ESTATUS CALCULADO POR CREDITO
SELECT
    c.id,
    c.clave_contrato,
    c.estatus AS estatus_guardado,
    CASE
        WHEN SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                      THEN 1 ELSE 0 END) = 0
             THEN 'Liquidado'
        WHEN SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                       AND a.fecha_vencimiento < CURDATE()
                      THEN 1 ELSE 0 END) > 0
             THEN 'Moroso'
        ELSE 'Activo'
    END AS estatus_calculado
FROM creditos c
LEFT JOIN amortizaciones a ON a.credito_id = c.id
GROUP BY c.id, c.clave_contrato, c.estatus
ORDER BY c.id;



-- 3. GUARDADO CONTRA CALCULADO
SELECT
    t.estatus_guardado,
    t.estatus_calculado,
    COUNT(*) AS creditos,
    SUM(t.estatus_guardado <> t.estatus_calculado) AS no_coinciden
FROM (
    SELECT
        c.id,
        c.estatus AS estatus_guardado,
        CASE
            WHEN SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                          THEN 1 ELSE 0 END) = 0
                 THEN 'Liquidado'
            WHEN SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                           AND a.fecha_vencimiento < CURDATE()
                          THEN 1 ELSE 0 END) > 0
                 THEN 'Moroso'
            ELSE 'Activo'
        END AS estatus_calculado
    FROM creditos c
    LEFT JOIN amortizaciones a ON a.credito_id = c.id
    GROUP BY c.id, c.estatus
) t
GROUP BY t.estatus_guardado, t.estatus_calculado
ORDER BY no_coinciden DESC, creditos DESC;



-- 4. ACTIVO QUE DEBERIA SER MOROSO
SELECT
    c.id,
    c.clave_contrato,
    ac.nombre_completo AS acreditado,
    c.monto_otorgado,
    c.monto_otorgado - SUM(a.capital_pagado) AS saldo_insoluto,
    SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
              AND a.fecha_vencimiento < CURDATE()
             THEN 1 ELSE 0 END) AS cuotas_vencidas,
    SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
              AND a.fecha_vencimiento < CURDATE()
             THEN a.pago_restante ELSE 0 END) AS monto_vencido,
    MIN(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
              AND a.fecha_vencimiento < CURDATE()
             THEN a.fecha_vencimiento END) AS vencida_desde,
    DATEDIFF(CURDATE(), MIN(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                                  AND a.fecha_vencimiento < CURDATE()
                                 THEN a.fecha_vencimiento END)) AS dias_vencido
FROM creditos c
JOIN acreditados ac ON ac.id = c.acreditado_id
JOIN amortizaciones a ON a.credito_id = c.id
WHERE c.estatus = 'Activo'
GROUP BY c.id, c.clave_contrato, ac.nombre_completo, c.monto_otorgado
HAVING cuotas_vencidas > 0
ORDER BY dias_vencido DESC;



-- 5. ACTIVO QUE DEBERIA SER MOROSO, POR ANTIGUEDAD
SELECT
    CASE
        WHEN t.dias_vencido <= 30 THEN '1-30'
        WHEN t.dias_vencido <= 60 THEN '31-60'
        WHEN t.dias_vencido <= 90 THEN '61-90'
        ELSE 'mas de 90'
    END AS antiguedad,
    COUNT(*) AS creditos,
    SUM(t.saldo_insoluto) AS saldo_insoluto,
    SUM(t.monto_vencido) AS monto_vencido
FROM (
    SELECT
        c.id,
        c.monto_otorgado - SUM(a.capital_pagado) AS saldo_insoluto,
        SUM(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                  AND a.fecha_vencimiento < CURDATE()
                 THEN a.pago_restante ELSE 0 END) AS monto_vencido,
        DATEDIFF(CURDATE(), MIN(CASE WHEN a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
                                      AND a.fecha_vencimiento < CURDATE()
                                     THEN a.fecha_vencimiento END)) AS dias_vencido
    FROM creditos c
    JOIN amortizaciones a ON a.credito_id = c.id
    WHERE c.estatus = 'Activo'
    GROUP BY c.id, c.monto_otorgado
    HAVING dias_vencido IS NOT NULL
) t
GROUP BY antiguedad
ORDER BY MIN(t.dias_vencido);



-- 6. CARTERA VENCIDA QUE VE EL DASHBOARD CONTRA LA REAL
SELECT
    c.estatus,
    COUNT(DISTINCT c.id) AS creditos,
    COUNT(*) AS cuotas_vencidas,
    SUM(a.pago_restante) AS pago_restante_total,
    SUM(CASE WHEN a.estado = 'Pendiente' THEN a.pago_restante ELSE 0 END) AS pago_restante_pendiente
FROM amortizaciones a
JOIN creditos c ON c.id = a.credito_id
WHERE a.estado NOT IN ('Pagado','Condonado','Reestructurada','Gracia')
  AND a.fecha_vencimiento < CURDATE()
GROUP BY c.estatus;



-- 7. HISTORIA DE UN CREDITO
SELECT
    a.numero_cuota,
    a.fecha_vencimiento,
    a.estado,
    a.cuota_fija,
    a.capital_pagado,
    a.pago_restante,
    a.fecha_ultimo_pago,
    a.interes_moratorio_generado,
    DATEDIFF(CURDATE(), a.fecha_vencimiento) AS dias_desde_vencimiento
FROM amortizaciones a
WHERE a.credito_id = 3
ORDER BY a.numero_cuota;



-- 8. PAGOS DE UN CREDITO
SELECT p.id, p.fecha_pago, p.monto_recibido, p.cancelado, p.created_at
FROM pagos p
WHERE p.credito_id = 3
ORDER BY p.fecha_pago;
