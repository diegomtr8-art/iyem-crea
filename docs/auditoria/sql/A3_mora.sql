
-- 1. TOTALES
SELECT
    COUNT(*) AS cuotas_desincronizadas,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS dinero_en_juego,
    MAX(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS peor_caso
FROM amortizaciones am
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01;



-- 2. POR ESTADO DE LA CUOTA
SELECT
    am.estado,
    COUNT(*) AS cuotas,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS dinero_en_juego,
    MAX(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS peor_caso,
    SUM(am.moratorio_acumulado > am.interes_moratorio_generado) AS portal_muestra_mas,
    SUM(am.moratorio_acumulado < am.interes_moratorio_generado) AS portal_muestra_menos
FROM amortizaciones am
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
GROUP BY am.estado
ORDER BY dinero_en_juego DESC;



-- 3. POR ESTATUS DEL CRÉDITO
SELECT
    c.estatus AS estatus_credito,
    COUNT(*) AS cuotas,
    COUNT(DISTINCT c.id) AS creditos,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS dinero_en_juego,
    MAX(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS peor_caso,
    SUM(am.moratorio_acumulado > am.interes_moratorio_generado) AS portal_muestra_mas,
    SUM(am.moratorio_acumulado < am.interes_moratorio_generado) AS portal_muestra_menos
FROM amortizaciones am
JOIN creditos c ON c.id = am.credito_id
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
GROUP BY c.estatus
ORDER BY dinero_en_juego DESC;



-- 4. POR ANTIGÜEDAD DEL VENCIMIENTO

SELECT
    CASE
        WHEN DATEDIFF(CURDATE(), am.fecha_vencimiento) <= 0   THEN '1. No vencida'
        WHEN DATEDIFF(CURDATE(), am.fecha_vencimiento) <= 5   THEN '2. 1-5 días (gracia)'
        WHEN DATEDIFF(CURDATE(), am.fecha_vencimiento) <= 30  THEN '3. 6-30 días'
        WHEN DATEDIFF(CURDATE(), am.fecha_vencimiento) <= 90  THEN '4. 31-90 días'
        WHEN DATEDIFF(CURDATE(), am.fecha_vencimiento) <= 180 THEN '5. 91-180 días'
        ELSE '6. Más de 180 días'
    END AS antiguedad,
    COUNT(*) AS cuotas,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS dinero_en_juego,
    MAX(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS peor_caso,
    SUM(am.moratorio_acumulado > am.interes_moratorio_generado) AS portal_muestra_mas,
    SUM(am.moratorio_acumulado < am.interes_moratorio_generado) AS portal_muestra_menos
FROM amortizaciones am
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
GROUP BY antiguedad
ORDER BY antiguedad;



-- 5. POR CRÉDITO

SELECT
    c.id AS credito_id,
    c.clave_contrato,
    a.nombre_completo AS acreditado,
    c.estatus AS estatus_credito,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01) AS cuotas_desincronizadas,
    COUNT(*) AS cuotas_del_credito,
    SUM(am.moratorio_acumulado) AS lo_ve_portal_y_pdf,
    SUM(am.interes_moratorio_generado) AS lo_ve_consulta_publica,
    SUM(am.moratorio_acumulado - am.interes_moratorio_generado) AS diferencia,
    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS diferencia_absoluta
FROM amortizaciones am
JOIN creditos c    ON c.id = am.credito_id
JOIN acreditados a ON a.id = c.acreditado_id
GROUP BY c.id, c.clave_contrato, a.nombre_completo, c.estatus
HAVING diferencia_absoluta > 0.01
ORDER BY diferencia_absoluta DESC;



-- 6. DETALLE POR CUOTA
SELECT
    c.clave_contrato,
    a.nombre_completo AS acreditado,
    am.id AS amortizacion_id,
    am.numero_cuota,
    am.fecha_vencimiento,
    am.fecha_ultimo_pago,
    DATEDIFF(am.fecha_ultimo_pago, am.fecha_vencimiento) AS dias_de_atraso_del_pago,
    am.estado,
    am.moratorio_acumulado AS lo_ve_portal_y_pdf,
    am.interes_moratorio_generado AS lo_ve_consulta_publica,
    ROUND(am.moratorio_acumulado - am.interes_moratorio_generado, 2) AS diferencia,
    CASE
        WHEN am.estado IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
            THEN 'Permanente: el cron ya no toca esta cuota'
        ELSE 'Temporal: el cron la iguala en su próxima corrida'
    END AS tipo_desfase
FROM amortizaciones am
JOIN creditos c    ON c.id = am.credito_id
JOIN acreditados a ON a.id = c.acreditado_id
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
ORDER BY c.clave_contrato, am.numero_cuota;



-- 7. VERIFICAR LA REGLA QUE PREDICE EL DESFASE
SELECT
    CASE
        WHEN DATEDIFF(am.fecha_ultimo_pago, am.fecha_vencimiento) > 5
             AND c.tasa_interes_moratorio > 0
             AND ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
            THEN 'Predicho y desincronizado'
        WHEN DATEDIFF(am.fecha_ultimo_pago, am.fecha_vencimiento) > 5
             AND c.tasa_interes_moratorio = 0
            THEN 'Predicho sincronizado (tasa 0%)'
        WHEN ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
            THEN 'NO PREDICHO: desincronizada sin pago tardío'
        WHEN DATEDIFF(am.fecha_ultimo_pago, am.fecha_vencimiento) > 5
            THEN 'NO PREDICHO: pago tardío que sí quedó sincronizado'
        ELSE 'Sin pago tardío y sincronizada'
    END AS resultado,
    COUNT(*) AS cuotas
FROM amortizaciones am
JOIN creditos c ON c.id = am.credito_id
GROUP BY resultado
ORDER BY cuotas DESC;



-- 8. CUOTAS VENCIDAS SIN MORA EN NINGUNA COLUMNA

SELECT
    c.clave_contrato,
    a.nombre_completo AS acreditado,
    am.numero_cuota,
    am.fecha_vencimiento,
    DATEDIFF(CURDATE(), am.fecha_vencimiento) AS dias_vencida,
    ROUND(am.saldo_insoluto - am.capital_pagado, 2) AS saldo_vencido,
    c.tasa_interes_moratorio AS tasa_anual,
    ROUND(
        (am.saldo_insoluto - am.capital_pagado)
        * ((c.tasa_interes_moratorio / 100) / 360)
        * DATEDIFF(CURDATE(), am.fecha_vencimiento),
        2
    ) AS mora_que_deberia_tener
FROM amortizaciones am
JOIN creditos c    ON c.id = am.credito_id
JOIN acreditados a ON a.id = c.acreditado_id
WHERE am.estado NOT IN ('Pagado', 'Condonado', 'Reestructurada', 'Gracia')
  AND DATEDIFF(CURDATE(), am.fecha_vencimiento) > 5
  AND c.tasa_interes_moratorio > 0
  AND am.moratorio_acumulado <= 0.01
  AND am.interes_moratorio_generado <= 0.01
ORDER BY dias_vencida DESC;
