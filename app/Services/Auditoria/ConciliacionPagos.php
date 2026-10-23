<?php

namespace App\Services\Auditoria;

use Illuminate\Support\Facades\DB;

class ConciliacionPagos implements Comprobacion
{
    public function clave(): string
    {
        return 'conciliacion';
    }

    public function nombre(): string
    {
        return 'Conciliación pagos ↔ amortizaciones';
    }

    public function ejecutar(): ResultadoComprobacion
    {
        $resumen = DB::select("
            SELECT
                COUNT(*) AS creditos_revisados,
                SUM(ABS(COALESCE(pg.cap,0) - COALESCE(am.cap,0)) > 0.01
                 OR ABS(COALESCE(pg.ord,0) - COALESCE(am.ord,0)) > 0.01
                 OR ABS(COALESCE(pg.mor,0) - COALESCE(am.mor,0)) > 0.01
                 OR COALESCE(am.com,0) > 0.01) AS creditos_descuadrados,
                ROUND(SUM(ABS(COALESCE(pg.cap,0) - COALESCE(am.cap,0))), 2) AS dif_capital,
                ROUND(SUM(ABS(COALESCE(pg.ord,0) - COALESCE(am.ord,0))), 2) AS dif_ordinario,
                ROUND(SUM(ABS(COALESCE(pg.mor,0) - COALESCE(am.mor,0))), 2) AS dif_moratorio,
                ROUND(SUM(COALESCE(am.com,0)), 2) AS dif_comisiones
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
            ) am ON am.credito_id = c.id
        ")[0];

        $monto = (float) $resumen->dif_capital
            + (float) $resumen->dif_ordinario
            + (float) $resumen->dif_moratorio
            + (float) $resumen->dif_comisiones;

        $detalle = DB::select("
            SELECT * FROM (
                SELECT
                    c.id AS credito_id,
                    c.clave_contrato,
                    ROUND(COALESCE(pg.cap,0) - COALESCE(am.cap,0), 2) AS dif_capital,
                    ROUND(COALESCE(pg.ord,0) - COALESCE(am.ord,0), 2) AS dif_ordinario,
                    ROUND(COALESCE(pg.mor,0) - COALESCE(am.mor,0), 2) AS dif_moratorio,
                    ROUND(COALESCE(am.com,0), 2) AS dif_comision
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
                ) am ON am.credito_id = c.id
            ) t
            WHERE ABS(dif_capital) > 0.01
               OR ABS(dif_ordinario) > 0.01
               OR ABS(dif_moratorio) > 0.01
               OR dif_comision > 0.01
            ORDER BY ABS(dif_capital) DESC
        ");

        return new ResultadoComprobacion(
            clave: $this->clave(),
            nombre: $this->nombre(),
            revisados: (int) $resumen->creditos_revisados,
            unidad: 'créditos',
            hallazgos: (int) $resumen->creditos_descuadrados,
            monto: $monto,
            detalle: $detalle,
        );
    }
}
