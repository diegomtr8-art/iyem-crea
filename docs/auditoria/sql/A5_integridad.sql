
-- 1. CAPITAL: POR CREDITO
SELECT
    c.id,
    c.clave_contrato,
    c.monto_otorgado,
    SUM(a.capital_esperado) AS capital_en_plan,
    ROUND(SUM(a.capital_esperado) - c.monto_otorgado, 2) AS diferencia
FROM creditos c
JOIN amortizaciones a ON a.credito_id = c.id
GROUP BY c.id, c.clave_contrato, c.monto_otorgado
ORDER BY ABS(ROUND(SUM(a.capital_esperado) - c.monto_otorgado, 2)) DESC;



-- 2. CAPITAL: RESUMEN
SELECT
    COUNT(*) AS creditos,
    SUM(d = 0) AS exactos,
    SUM(d <> 0 AND ABS(d) <= 0.01) AS redondeo,
    SUM(ABS(d) > 0.01) AS error,
    (SELECT COUNT(*) FROM creditos c2
      WHERE NOT EXISTS (SELECT 1 FROM amortizaciones a2 WHERE a2.credito_id = c2.id)) AS sin_plan
FROM (
    SELECT ROUND(SUM(a.capital_esperado) - c.monto_otorgado, 2) AS d
    FROM creditos c
    JOIN amortizaciones a ON a.credito_id = c.id
    GROUP BY c.id
) t;



-- 3. PLAZO Y GRACIA: POR CREDITO
SELECT
    c.id,
    c.clave_contrato,
    m.nombre AS modalidad,
    c.plazo_meses,
    SUM(a.estado <> 'Gracia') AS cuotas_reales,
    IF(m.nombre LIKE '%sustentable%', 3, 0) AS gracia_esperada,
    SUM(a.estado = 'Gracia') AS gracia_real
FROM creditos c
JOIN amortizaciones a ON a.credito_id = c.id
LEFT JOIN modalidad_creas m ON m.id = c.modalidad_id
GROUP BY c.id, c.clave_contrato, m.nombre, c.plazo_meses
ORDER BY ABS(SUM(a.estado <> 'Gracia') - c.plazo_meses) DESC, c.id;



-- 4. PLAZO Y GRACIA: RESUMEN POR MODALIDAD
SELECT
    m.nombre AS modalidad,
    COUNT(*) AS creditos,
    SUM(t.cuotas_reales = t.plazo_meses) AS plazo_correcto,
    SUM(t.gracia_real = IF(m.nombre LIKE '%sustentable%', 3, 0)) AS gracia_correcta,
    MIN(t.meses_a_cuota1) AS meses_a_cuota1
FROM (
    SELECT
        c.id,
        c.modalidad_id,
        c.plazo_meses,
        SUM(a.estado <> 'Gracia') AS cuotas_reales,
        SUM(a.estado = 'Gracia') AS gracia_real,
        TIMESTAMPDIFF(MONTH, c.fecha_entrega, MIN(a.fecha_vencimiento)) AS meses_a_cuota1
    FROM creditos c
    JOIN amortizaciones a ON a.credito_id = c.id
    GROUP BY c.id, c.modalidad_id, c.plazo_meses, c.fecha_entrega
) t
LEFT JOIN modalidad_creas m ON m.id = t.modalidad_id
GROUP BY m.nombre;



-- 5. SALDO: CUOTA CONTRA LA ANTERIOR (COMPARACION DIRECTA)
SELECT
    credito_id,
    SUM(saldo_prev IS NOT NULL AND saldo_insoluto >= saldo_prev) AS cuotas_donde_no_baja,
    MAX(CASE WHEN desde_el_final = 1 THEN ROUND(saldo_insoluto - capital_esperado, 2) END) AS saldo_final
FROM (
    SELECT
        credito_id,
        saldo_insoluto,
        capital_esperado,
        LAG(saldo_insoluto) OVER (PARTITION BY credito_id ORDER BY numero_cuota) AS saldo_prev,
        ROW_NUMBER() OVER (PARTITION BY credito_id ORDER BY numero_cuota DESC) AS desde_el_final
    FROM amortizaciones
    WHERE estado <> 'Gracia'
) t
GROUP BY credito_id
HAVING cuotas_donde_no_baja > 0 OR ABS(saldo_final) > 0.01
ORDER BY cuotas_donde_no_baja DESC, credito_id;



-- 6. SALDO: CONTRA EL SALDO RECONSTRUIDO DESDE EL MONTO
SELECT
    c.id,
    c.clave_contrato,
    r.filas_mal,
    r.peor_dif,
    r.saldo_final
FROM creditos c
JOIN (
    SELECT
        credito_id,
        SUM(ABS(saldo_insoluto - (abierto - capital_pagado)) > 0.01) AS filas_mal,
        MAX(ABS(saldo_insoluto - (abierto - capital_pagado))) AS peor_dif,
        MAX(CASE WHEN desde_el_final = 1 THEN ROUND(abierto - capital_esperado, 2) END) AS saldo_final
    FROM (
        SELECT
            a.credito_id,
            a.saldo_insoluto,
            a.capital_esperado,
            a.capital_pagado,
            c2.monto_otorgado - COALESCE(SUM(a.capital_esperado) OVER (
                PARTITION BY a.credito_id ORDER BY a.numero_cuota
                ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS abierto,
            ROW_NUMBER() OVER (PARTITION BY a.credito_id ORDER BY a.numero_cuota DESC) AS desde_el_final
        FROM amortizaciones a
        JOIN creditos c2 ON c2.id = a.credito_id
        WHERE a.estado <> 'Gracia'
    ) t
    GROUP BY credito_id
) r ON r.credito_id = c.id
ORDER BY r.filas_mal DESC, r.peor_dif DESC;



-- 7. CUOTA FIJA: VALORES DISTINTOS SIN CONTAR LA ULTIMA CUOTA
SELECT
    a.credito_id,
    COUNT(DISTINCT a.cuota_fija) AS valores_distintos,
    MIN(a.cuota_fija) AS minimo,
    MAX(a.cuota_fija) AS maximo,
    (SELECT COUNT(*) FROM reestructuraciones r WHERE r.credito_id = a.credito_id) AS reestructuraciones
FROM amortizaciones a
WHERE a.estado <> 'Gracia'
  AND a.numero_cuota < (SELECT MAX(x.numero_cuota) FROM amortizaciones x WHERE x.credito_id = a.credito_id)
GROUP BY a.credito_id
HAVING valores_distintos > 1;



-- 8. CUOTA FIJA: DIFERENCIA DE LA ULTIMA CUOTA CONTRA LA CUOTA 1
SELECT
    COUNT(*) AS creditos,
    SUM(u.cuota_fija <> n.cuota_fija) AS ultima_distinta,
    MIN(u.cuota_fija - n.cuota_fija) AS dif_minima,
    MAX(u.cuota_fija - n.cuota_fija) AS dif_maxima
FROM amortizaciones u
JOIN amortizaciones n ON n.credito_id = u.credito_id AND n.numero_cuota = 1
WHERE u.numero_cuota = (SELECT MAX(x.numero_cuota) FROM amortizaciones x WHERE x.credito_id = u.credito_id);



-- 9. CREDITOS QUE FALLAN MAS DE UNA COMPROBACION
SELECT
    c.id,
    c.clave_contrato,
    ABS(cap.d) > 0.01 AS falla_capital,
    (pl.cuotas_reales <> c.plazo_meses OR pl.gracia_real <> IF(m.nombre LIKE '%sustentable%', 3, 0)) AS falla_plazo,
    (sa.filas_mal > 0 OR ABS(sa.saldo_final) > 0.01) AS falla_saldo,
    (cf.v > 1 AND NOT EXISTS (SELECT 1 FROM reestructuraciones r WHERE r.credito_id = c.id)) AS falla_cuota_fija
FROM creditos c
LEFT JOIN modalidad_creas m ON m.id = c.modalidad_id
JOIN (
    SELECT a.credito_id, SUM(a.capital_esperado) - MAX(c3.monto_otorgado) AS d
    FROM amortizaciones a JOIN creditos c3 ON c3.id = a.credito_id
    GROUP BY a.credito_id
) cap ON cap.credito_id = c.id
JOIN (
    SELECT credito_id, SUM(estado <> 'Gracia') AS cuotas_reales, SUM(estado = 'Gracia') AS gracia_real
    FROM amortizaciones GROUP BY credito_id
) pl ON pl.credito_id = c.id
JOIN (
    SELECT
        credito_id,
        SUM(ABS(saldo_insoluto - (abierto - capital_pagado)) > 0.01) AS filas_mal,
        MAX(CASE WHEN desde_el_final = 1 THEN ROUND(abierto - capital_esperado, 2) END) AS saldo_final
    FROM (
        SELECT
            a.credito_id, a.saldo_insoluto, a.capital_esperado, a.capital_pagado,
            c2.monto_otorgado - COALESCE(SUM(a.capital_esperado) OVER (
                PARTITION BY a.credito_id ORDER BY a.numero_cuota
                ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS abierto,
            ROW_NUMBER() OVER (PARTITION BY a.credito_id ORDER BY a.numero_cuota DESC) AS desde_el_final
        FROM amortizaciones a
        JOIN creditos c2 ON c2.id = a.credito_id
        WHERE a.estado <> 'Gracia'
    ) t
    GROUP BY credito_id
) sa ON sa.credito_id = c.id
JOIN (
    SELECT a.credito_id, COUNT(DISTINCT a.cuota_fija) AS v
    FROM amortizaciones a
    WHERE a.estado <> 'Gracia'
      AND a.numero_cuota < (SELECT MAX(x.numero_cuota) FROM amortizaciones x WHERE x.credito_id = a.credito_id)
    GROUP BY a.credito_id
) cf ON cf.credito_id = c.id
HAVING falla_capital + falla_plazo + falla_saldo + falla_cuota_fija > 1
ORDER BY c.id;
