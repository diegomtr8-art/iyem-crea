-- 1. DETALLE POR CREDITO
SELECT
    c.id AS credito_id,
    c.clave_contrato,
    ac.nombre_completo AS acreditado,
    m.nombre AS modalidad,
    ac.municipio,
    v.saldo_vencido,
    v.vencimiento_mas_antiguo,
    v.dias_atraso,
    CASE
        WHEN v.dias_atraso <= 30  THEN 1
        WHEN v.dias_atraso <= 60  THEN 2
        WHEN v.dias_atraso <= 90  THEN 3
        WHEN v.dias_atraso <= 180 THEN 4
        ELSE 5
    END AS orden_cubeta,
    CASE
        WHEN v.dias_atraso <= 30  THEN '1-30'
        WHEN v.dias_atraso <= 60  THEN '31-60'
        WHEN v.dias_atraso <= 90  THEN '61-90'
        WHEN v.dias_atraso <= 180 THEN '91-180'
        ELSE 'Mas de 180'
    END AS cubeta,
    v.cuotas_vencidas
FROM (
    SELECT
        a.credito_id,
        SUM(a.pago_restante) AS saldo_vencido,
        MIN(a.fecha_vencimiento) AS vencimiento_mas_antiguo,
        DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) AS dias_atraso,
        COUNT(*) AS cuotas_vencidas
    FROM amortizaciones a
    WHERE a.fecha_vencimiento < CURDATE()
      AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
      AND a.pago_restante > 0
    GROUP BY a.credito_id
) v
JOIN creditos c         ON c.id  = v.credito_id
JOIN acreditados ac     ON ac.id = c.acreditado_id
JOIN modalidad_creas m  ON m.id  = c.modalidad_id
ORDER BY v.dias_atraso DESC, v.saldo_vencido DESC;



-- 2. RESUMEN POR CUBETA
SELECT
    k.orden_cubeta,
    k.cubeta,
    COUNT(d.credito_id) AS creditos,
    COALESCE(SUM(d.saldo_vencido), 0) AS saldo_vencido,
    ROUND(100 * COALESCE(SUM(d.saldo_vencido), 0) / SUM(SUM(d.saldo_vencido)) OVER (), 2) AS porcentaje
FROM (
              SELECT 1 AS orden_cubeta, '1-30'       AS cubeta
    UNION ALL SELECT 2,                 '31-60'
    UNION ALL SELECT 3,                 '61-90'
    UNION ALL SELECT 4,                 '91-180'
    UNION ALL SELECT 5,                 'Mas de 180'
) k
LEFT JOIN (
    SELECT
        a.credito_id,
        SUM(a.pago_restante) AS saldo_vencido,
        CASE
            WHEN DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) <= 30  THEN 1
            WHEN DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) <= 60  THEN 2
            WHEN DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) <= 90  THEN 3
            WHEN DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) <= 180 THEN 4
            ELSE 5
        END AS orden_cubeta
    FROM amortizaciones a
    WHERE a.fecha_vencimiento < CURDATE()
      AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
      AND a.pago_restante > 0
    GROUP BY a.credito_id
) d ON d.orden_cubeta = k.orden_cubeta
GROUP BY k.orden_cubeta, k.cubeta
ORDER BY k.orden_cubeta;



-- 3. CUADRE: TOTAL DE CUOTAS CONTRA TOTAL AGRUPADO POR CREDITO
SELECT
    (SELECT SUM(a.pago_restante)
       FROM amortizaciones a
       JOIN creditos c        ON c.id  = a.credito_id
       JOIN acreditados ac    ON ac.id = c.acreditado_id
       JOIN modalidad_creas m ON m.id  = c.modalidad_id
      WHERE a.fecha_vencimiento < CURDATE()
        AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
        AND a.pago_restante > 0) AS total_con_joins,
    (SELECT SUM(a.pago_restante)
       FROM amortizaciones a
      WHERE a.fecha_vencimiento < CURDATE()
        AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
        AND a.pago_restante > 0) AS total_sin_joins,
    (SELECT COUNT(DISTINCT a.credito_id)
       FROM amortizaciones a
      WHERE a.fecha_vencimiento < CURDATE()
        AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
        AND a.pago_restante > 0) AS creditos;



-- 4. CUOTAS VENCIDAS DE UN CREDITO (VERIFICACION A MANO)
SET @credito = 1;
SELECT
    a.numero_cuota,
    a.fecha_vencimiento,
    DATEDIFF(CURDATE(), a.fecha_vencimiento) AS dias,
    a.estado,
    a.cuota_fija,
    a.pago_restante
FROM amortizaciones a
WHERE a.credito_id = @credito
  AND a.fecha_vencimiento < CURDATE()
ORDER BY a.numero_cuota;



-- 5. EFECTO DE LOS 5 DIAS DE GRACIA EN LA PRIMERA CUBETA
SELECT
    SUM(dias_atraso <= 5) AS creditos_dias_1_a_5,
    SUM(IF(dias_atraso <= 5, saldo_vencido, 0)) AS saldo_dias_1_a_5,
    SUM(dias_atraso BETWEEN 6 AND 30) AS creditos_dias_6_a_30,
    SUM(IF(dias_atraso BETWEEN 6 AND 30, saldo_vencido, 0)) AS saldo_dias_6_a_30
FROM (
    SELECT
        a.credito_id,
        SUM(a.pago_restante) AS saldo_vencido,
        DATEDIFF(CURDATE(), MIN(a.fecha_vencimiento)) AS dias_atraso
    FROM amortizaciones a
    WHERE a.fecha_vencimiento < CURDATE()
      AND a.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
      AND a.pago_restante > 0
    GROUP BY a.credito_id
) d;



-- 6. CUOTAS QUE CADA ESTADO EXCLUIDO SACA DEL REPORTE
SELECT
    a.estado,
    COUNT(*) AS cuotas_vencidas,
    COUNT(DISTINCT a.credito_id) AS creditos,
    SUM(a.pago_restante) AS pago_restante
FROM amortizaciones a
WHERE a.fecha_vencimiento < CURDATE()
GROUP BY a.estado
ORDER BY a.estado;
