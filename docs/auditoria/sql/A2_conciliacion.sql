-- A.2 · Conciliación entre `pagos` y `amortizaciones`
-- Autor: Jesús Alejandro Mena Márquez
--
-- Qué hace: por cada crédito compara lo que la caja dice que aplicó
-- (tabla `pagos`) contra lo que las cuotas dicen que recibieron
-- (tabla `amortizaciones`). Si el sistema registró bien cada pago,
-- las dos cifras deben ser iguales.
--
-- Solo lectura. Todas las consultas son SELECT: no modifican datos.
--
-- Criterios usados en todas las consultas:
--   * Se excluyen los pagos cancelados (`cancelado = 1`). Al cancelar,
--     el sistema revierte las cuotas, así que sumarlos generaría
--     diferencias falsas.
--   * Una diferencia menor o igual a $0.01 se considera cuadrada.
--   * `diferencia` = pagos − amortizaciones.
--       positiva → entró por caja más de lo que registran las cuotas
--       negativa → las cuotas registran más de lo que entró por caja



-- 1. CAPITAL
--    pagos.aplicado_capital  vs  amortizaciones.capital_pagado
SELECT
    c.id,
    c.clave_contrato,
    c.estatus,
    COALESCE(p.total_pagos, 0) AS capital_segun_pagos,
    COALESCE(a.total_amort, 0) AS capital_segun_amortizaciones,
    COALESCE(p.total_pagos, 0) - COALESCE(a.total_amort, 0) AS diferencia
FROM creditos c
LEFT JOIN (
    SELECT credito_id, SUM(aplicado_capital) AS total_pagos
    FROM pagos
    WHERE cancelado = 0
    GROUP BY credito_id
) p ON p.credito_id = c.id
LEFT JOIN (
    SELECT credito_id, SUM(capital_pagado) AS total_amort
    FROM amortizaciones
    GROUP BY credito_id
) a ON a.credito_id = c.id
HAVING ABS(diferencia) > 0.01
ORDER BY ABS(diferencia) DESC;


-- 2. INTERÉS ORDINARIO
--    pagos.aplicado_ordinario  vs  amortizaciones.interes_ordinario_pagado
SELECT
    c.id,
    c.clave_contrato,
    c.estatus,
    COALESCE(p.total_pagos, 0) AS ordinario_segun_pagos,
    COALESCE(a.total_amort, 0) AS ordinario_segun_amortizaciones,
    COALESCE(p.total_pagos, 0) - COALESCE(a.total_amort, 0) AS diferencia
FROM creditos c
LEFT JOIN (
    SELECT credito_id, SUM(aplicado_ordinario) AS total_pagos
    FROM pagos
    WHERE cancelado = 0
    GROUP BY credito_id
) p ON p.credito_id = c.id
LEFT JOIN (
    SELECT credito_id, SUM(interes_ordinario_pagado) AS total_amort
    FROM amortizaciones
    GROUP BY credito_id
) a ON a.credito_id = c.id
HAVING ABS(diferencia) > 0.01
ORDER BY ABS(diferencia) DESC;


 
-- 3. INTERÉS MORATORIO
--    pagos.aplicado_mora  vs  amortizaciones.interes_moratorio_pagado

SELECT
    c.id,
    c.clave_contrato,
    c.estatus,
    COALESCE(p.total_pagos, 0) AS moratorio_segun_pagos,
    COALESCE(a.total_amort, 0) AS moratorio_segun_amortizaciones,
    COALESCE(p.total_pagos, 0) - COALESCE(a.total_amort, 0) AS diferencia
FROM creditos c
LEFT JOIN (
    SELECT credito_id, SUM(aplicado_mora) AS total_pagos
    FROM pagos
    WHERE cancelado = 0
    GROUP BY credito_id
) p ON p.credito_id = c.id
LEFT JOIN (
    SELECT credito_id, SUM(interes_moratorio_pagado) AS total_amort
    FROM amortizaciones
    GROUP BY credito_id
) a ON a.credito_id = c.id
HAVING ABS(diferencia) > 0.01
ORDER BY ABS(diferencia) DESC;


-- 4. COMISIONES
--    (sin columna en pagos)  vs  amortizaciones.comision_pagada
--
--    La tabla `pagos` no tiene columna de comisión, y ningún proceso
--    del código escribe `amortizaciones.comision_pagada` (revisado en
--    app/ al 2026-09-21). Por eso el lado de pagos se toma como 0.
--    Cualquier fila que salga aquí es comisión que llegó a las cuotas
--    por otro camino (importación o edición manual) y no se puede
--    rastrear hasta un pago de caja.
SELECT
    c.id,
    c.clave_contrato,
    c.estatus,
    0 AS comision_segun_pagos,
    COALESCE(a.total_amort, 0) AS comision_segun_amortizaciones,
    0 - COALESCE(a.total_amort, 0) AS diferencia
FROM creditos c
LEFT JOIN (
    SELECT credito_id, SUM(comision_pagada) AS total_amort
    FROM amortizaciones
    GROUP BY credito_id
) a ON a.credito_id = c.id
HAVING ABS(diferencia) > 0.01
ORDER BY ABS(diferencia) DESC;



-- 5. HUELLA DEL ABONO A CAPITAL (Reducir Cuota / Reducir Plazo)
--
--    Cuando un pago deja sobrante y el operativo elige Reducir Cuota o
--    Reducir Plazo, el sistema suma ese sobrante a
--    `pagos.aplicado_capital`, pero NO lo anota en el JSON
--    `cuotas_cubiertas`, que solo registra lo aplicado cuota por cuota.
--    Por eso, en esos pagos:
--
--        aplicado_capital  >  suma de "cap" dentro de cuotas_cubiertas
--
--    y la diferencia es el sobrante que se mandó a capital. Esta
--    consulta encuentra esos pagos sin necesitar la columna
--    `tipo_abono` (que solo existe desde la migración del 2026-09-09).
--
--    Ojo con la fecha del pago:
--      * Código de develop entre 2026-09-07 (e80163d) y 2026-09-21
--        (210f915): el sobrante NO se anotaba en capital_pagado. Esos
--        pagos deben aparecer también en la consulta 1.
--      * Código de develop desde 210f915: el sobrante sí se anota en
--        capital_pagado. Aparecen aquí, pero deben cuadrar en la 1.
--      * Código de main (51fbf75): este camino no se puede ejecutar;
--        un sobrante solo ocurre en liquidación total, que no pasa por
--        aquí. Con main no deberían salir filas.
--
--    Compatible con MariaDB 10.4 (sin JSON_TABLE): recorre las
--    posiciones 0..99 del arreglo JSON con una tabla de números.
SELECT
    p.id            AS pago_id,
    p.folio,
    p.credito_id,
    c.clave_contrato,
    p.fecha_pago,
    p.cancelado,
    p.monto_recibido,
    p.aplicado_capital,
    ROUND(SUM(COALESCE(JSON_EXTRACT(p.cuotas_cubiertas, CONCAT('$[', n.i, '].cap')), 0)), 2)
        AS capital_en_cuotas_cubiertas,
    ROUND(p.aplicado_capital
        - SUM(COALESCE(JSON_EXTRACT(p.cuotas_cubiertas, CONCAT('$[', n.i, '].cap')), 0)), 2)
        AS sobrante_a_capital
FROM pagos p
JOIN creditos c ON c.id = p.credito_id
LEFT JOIN (
    SELECT d.x + u.x * 10 AS i
    FROM (SELECT 0 x UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
    CROSS JOIN
         (SELECT 0 x UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
) n ON n.i < COALESCE(JSON_LENGTH(p.cuotas_cubiertas), 0)
GROUP BY p.id, p.folio, p.credito_id, c.clave_contrato, p.fecha_pago,
         p.cancelado, p.monto_recibido, p.aplicado_capital
HAVING sobrante_a_capital > 0.01
ORDER BY sobrante_a_capital DESC;


-- 6. HUELLA DE «REDUCIR PLAZO» EN LAS CUOTAS
--
--    Reducir Plazo deja una observación en las cuotas que toca. Según la
--    versión del código, la marca es distinta:
--      * 'Cuota eliminada por reducción de plazo' → develop entre
--        2026-09-08 (d84b55c) y 2026-09-21. La cuota queda en Pagado con
--        capital_esperado = 0 y SIN registrar el dinero en
--        capital_pagado.
--      * 'Liquidada por abono anticipado a capital' → develop del
--        2026-09-07 al 2026-09-08 (hasta d84b55c) y otra vez desde
--        210f915. La cuota queda en Pagado y además se marca
--        interes_ordinario_pagado = esperado, aunque ese interés no
--        entró por caja (revisar contra la consulta 2).
SELECT
    a.credito_id,
    c.clave_contrato,
    a.observaciones                   AS marca,
    COUNT(*)                          AS cuotas_tocadas,
    SUM(a.capital_esperado)           AS capital_esperado,
    SUM(a.capital_pagado)             AS capital_marcado_pagado,
    SUM(a.interes_ordinario_pagado)   AS interes_marcado_pagado
FROM amortizaciones a
JOIN creditos c ON c.id = a.credito_id
WHERE a.observaciones IN ('Cuota eliminada por reducción de plazo',
                          'Liquidada por abono anticipado a capital')
GROUP BY a.credito_id, c.clave_contrato, a.observaciones
ORDER BY a.credito_id;


-- 7. RESUMEN (cifras para la parte de arriba del reporte)
--    Cuántos créditos se revisaron y cuántos descuadran en al menos un
--    concepto, con la suma de las diferencias absolutas.
SELECT
    COUNT(*)                                            AS creditos_revisados,
    SUM(pg.credito_id IS NOT NULL)                      AS creditos_con_pagos_vigentes,
    SUM(ABS(COALESCE(pg.cap,0) - COALESCE(am.cap,0)) > 0.01
     OR ABS(COALESCE(pg.ord,0) - COALESCE(am.ord,0)) > 0.01
     OR ABS(COALESCE(pg.mor,0) - COALESCE(am.mor,0)) > 0.01
     OR COALESCE(am.com,0) > 0.01)                      AS creditos_descuadrados,
    ROUND(SUM(ABS(COALESCE(pg.cap,0) - COALESCE(am.cap,0))), 2) AS dif_capital,
    ROUND(SUM(ABS(COALESCE(pg.ord,0) - COALESCE(am.ord,0))), 2) AS dif_ordinario,
    ROUND(SUM(ABS(COALESCE(pg.mor,0) - COALESCE(am.mor,0))), 2) AS dif_moratorio,
    ROUND(SUM(COALESCE(am.com,0)), 2)                           AS dif_comisiones
FROM creditos c
LEFT JOIN (
    SELECT credito_id,
           SUM(aplicado_capital) AS cap, SUM(aplicado_ordinario) AS ord, SUM(aplicado_mora) AS mor
    FROM pagos WHERE cancelado = 0 GROUP BY credito_id
) pg ON pg.credito_id = c.id
LEFT JOIN (
    SELECT credito_id,
           SUM(capital_pagado) AS cap, SUM(interes_ordinario_pagado) AS ord,
           SUM(interes_moratorio_pagado) AS mor, SUM(comision_pagada) AS com
    FROM amortizaciones GROUP BY credito_id
) am ON am.credito_id = c.id;
