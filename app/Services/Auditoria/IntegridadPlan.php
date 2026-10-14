<?php

namespace App\Services\Auditoria;

use Illuminate\Support\Facades\DB;

class IntegridadPlan implements Comprobacion
{
    public function clave(): string
    {
        return 'integridad';
    }

    public function nombre(): string
    {
        return 'Integridad del plan de pagos';
    }

    public function ejecutar(): ResultadoComprobacion
    {
        $filas = DB::select("
    SELECT
        c.id AS credito_id,
        c.clave_contrato,
        CASE WHEN ABS(cap.d) > 0.01 THEN 1 ELSE 0 END AS falla_capital,
        CASE WHEN pl.cuotas_reales <> c.plazo_meses
              OR pl.gracia_real <> CASE WHEN m.nombre LIKE '%sustentable%' THEN 3 ELSE 0 END
             THEN 1 ELSE 0 END AS falla_plazo,
        CASE WHEN sa.filas_mal > 0 OR ABS(sa.saldo_final) > 0.01 THEN 1 ELSE 0 END AS falla_saldo,
        CASE WHEN cf.v > 1
              AND NOT EXISTS (SELECT 1 FROM reestructuraciones r WHERE r.credito_id = c.id)
             THEN 1 ELSE 0 END AS falla_cuota_fija
    FROM creditos c
    LEFT JOIN modalidad_creas m ON m.id = c.modalidad_id
    JOIN (
        SELECT a.credito_id, SUM(a.capital_esperado) - MAX(c3.monto_otorgado) AS d
        FROM amortizaciones a JOIN creditos c3 ON c3.id = a.credito_id
        GROUP BY a.credito_id
    ) cap ON cap.credito_id = c.id
    JOIN (
        SELECT credito_id,
               SUM(CASE WHEN estado <> 'Gracia' THEN 1 ELSE 0 END) AS cuotas_reales,
               SUM(CASE WHEN estado = 'Gracia' THEN 1 ELSE 0 END) AS gracia_real
        FROM amortizaciones GROUP BY credito_id
    ) pl ON pl.credito_id = c.id
    JOIN (
        SELECT
            credito_id,
            SUM(CASE WHEN ABS(saldo_insoluto - (abierto - capital_pagado)) > 0.01 THEN 1 ELSE 0 END) AS filas_mal,
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
    LEFT JOIN (
        SELECT a.credito_id, COUNT(DISTINCT a.cuota_fija) AS v
        FROM amortizaciones a
        WHERE a.estado <> 'Gracia'
          AND a.numero_cuota < (SELECT MAX(x.numero_cuota) FROM amortizaciones x WHERE x.credito_id = a.credito_id)
        GROUP BY a.credito_id
    ) cf ON cf.credito_id = c.id
    ORDER BY c.id
");

        $conFalla = array_values(array_filter($filas, fn ($f) =>
            $f->falla_capital + $f->falla_plazo + $f->falla_saldo + $f->falla_cuota_fija > 0
        ));

        return new ResultadoComprobacion(
            clave: $this->clave(),
            nombre: $this->nombre(),
            revisados: count($filas),
            unidad: 'créditos',
            hallazgos: count($conFalla),
            monto: null,
            detalle: $conFalla,
        );
    }
}