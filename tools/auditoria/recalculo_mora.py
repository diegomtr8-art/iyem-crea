#!/usr/bin/env python3
"""Recalcula la mora de los escenarios A.10 con cada base y la compara con lo medido (solo lee CSV).

python recalculo_mora.py --medido a10_medido.csv [--salida r.csv] [--detalle E6]
"""
import argparse
import csv
from decimal import Decimal, ROUND_HALF_UP, getcontext

getcontext().prec = 40
CENTAVO = Decimal("0.01")
DIAS_ANIO = 360  # Acuerdo, Anexo 7, cláusula séptima
DIAS_GRACIA = 5

# base => (cómo se calcula, columna medida que la usa)
BASES = {
    "cuota_restante": ("pago_restante", ["credit_service"]),
    "saldo_menos_capital_pagado": ("max(0, saldo_insoluto - capital_pagado)", ["cron_generado", "cron_acumulado", "caja_mora"]),
    "saldo_insoluto": ("saldo_insoluto", ["portal_mora"]),
    "acuerdo_capital_vencido": ("capital_esperado - capital_pagado", []),
}


def r2(x):
    return x.quantize(CENTAVO, rounding=ROUND_HALF_UP)


def bases(f):
    d = {k: Decimal(f[k]) for k in ("pago_restante", "saldo_insoluto", "capital_pagado", "capital_esperado")}
    return {
        "cuota_restante": d["pago_restante"],
        "saldo_menos_capital_pagado": max(Decimal(0), r2(d["saldo_insoluto"] - d["capital_pagado"])),
        "saldo_insoluto": d["saldo_insoluto"],
        "acuerdo_capital_vencido": d["capital_esperado"] - d["capital_pagado"],
    }


def tasa_diaria(f):
    return Decimal(f["tasa_moratoria"]) / 100 / DIAS_ANIO


def mora(base, tasa, dias):
    return r2(base * tasa * dias) if dias > DIAS_GRACIA else Decimal("0.00")


def detalle(f):
    b = bases(f)
    t = tasa_diaria(f)
    print(f"\nDetalle {f['escenario']}: cuota {f['numero_cuota']}, tasa diaria {t:.10f}")
    print(f"  capital vencido {b['acuerdo_capital_vencido']}, saldo insoluto {b['saldo_insoluto']}")
    print("  dia | del dia (Acuerdo) | acumulada | cobrable | del dia (saldo) | acumulada | cobrable")
    for dia in range(1, int(f["dias_atraso"]) + 1):
        a = b["acuerdo_capital_vencido"] * t
        s = b["saldo_menos_capital_pagado"] * t
        print(f"  {dia:3} | {a:.6f} | {a * dia:.6f} | {mora(b['acuerdo_capital_vencido'], t, dia)}"
              f" | {s:.6f} | {s * dia:.6f} | {mora(b['saldo_menos_capital_pagado'], t, dia)}")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--medido", required=True)
    ap.add_argument("--salida")
    ap.add_argument("--detalle")
    a = ap.parse_args()

    with open(a.medido, newline="", encoding="utf-8") as fh:
        filas = list(csv.DictReader(fh))

    salida = []
    discrepancias = 0
    for f in filas:
        b = bases(f)
        t = tasa_diaria(f)
        dias = int(f["dias_atraso"])
        recal = {k: mora(v, t, dias) for k, v in b.items()}
        acuerdo = recal["acuerdo_capital_vencido"]
        fila = dict(escenario=f["escenario"], dias=dias, estado=f["estado"])
        for k, (_, medidas) in BASES.items():
            fila[k] = recal[k]
            fila[f"{k}_vs_acuerdo"] = recal[k] - acuerdo
            for m in medidas:
                if Decimal(f[m]) != recal[k]:
                    discrepancias += 1
                    print(f"DISCREPANCIA {f['escenario']} {m}: medido {f[m]}, recalculado {recal[k]}")
        fila["portal_total"] = f["portal_total"]
        fila["caja_total"] = f["caja_total"]
        salida.append(fila)

    cols = ["escenario", "dias", "estado"] + list(BASES)
    print(" | ".join(cols))
    for s in salida:
        print(" | ".join(str(s[c]) for c in cols))
    print(f"\nMedido contra recalculado: {discrepancias} discrepancias")

    if a.salida:
        with open(a.salida, "w", newline="", encoding="utf-8") as fh:
            w = csv.DictWriter(fh, fieldnames=list(salida[0]))
            w.writeheader()
            w.writerows(salida)

    if a.detalle:
        detalle(next(f for f in filas if f["escenario"] == a.detalle))


if __name__ == "__main__":
    main()
