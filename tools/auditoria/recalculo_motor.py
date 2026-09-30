#!/usr/bin/env python3
"""Recalcula las tablas de amortizacion de CREA y las compara con lo guardado.

Replica app/Services/CreditService.php::generarTablaAmortizacion con Decimal.
Solo lee CSV; no toca la base de datos.

Uso:
    python recalculo_motor.py --creditos creditos.csv --amortizaciones amortizaciones.csv \
        --salida diferencias.csv [--tasa anual|mensual]
"""
import argparse
import calendar
import csv
from collections import Counter, defaultdict
from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP, getcontext

getcontext().prec = 40
CENTAVO = Decimal("0.01")
CAMPOS = ("cuota_fija", "capital_esperado", "interes_ordinario_esperado", "saldo_insoluto")
MESES_GRACIA_SUSTENTABLE = 3


def r2(x):
    # PHP round(): mitad hacia arriba (lejos de cero)
    return x.quantize(CENTAVO, rounding=ROUND_HALF_UP)


def add_months(d, n):
    """Suma meses como Carbon::addMonths (con desborde: 31-ene + 1 mes = 3-mar)."""
    m = d.month - 1 + n
    y, m = d.year + m // 12, m % 12 + 1
    dim = calendar.monthrange(y, m)[1]
    if d.day <= dim:
        return date(y, m, d.day)
    return date(y, m, dim) + timedelta(days=d.day - dim)


def clasificar(dif):
    a = abs(dif)
    if a == 0:
        return "exacta"
    if a <= CENTAVO:
        return "redondeo (<= 0.01)"
    if a <= Decimal("1.00"):
        return "menor (<= 1.00)"
    return "mayor (> 1.00)"


def tabla(credito, tasa_modo):
    monto = Decimal(credito["monto_otorgado"])
    plazo = int(credito["plazo_meses"])
    tasa = Decimal(credito["tasa_interes_ordinario"]) / 100
    div = 12 if tasa_modo == "anual" else 1
    i = tasa / div
    fecha = date.fromisoformat(credito["fecha_entrega"])
    gracia = MESES_GRACIA_SUSTENTABLE if "sustentable" in credito["modalidad"].lower() else 0

    filas = []
    for g in range(1, gracia + 1):
        filas.append(dict(numero_cuota=g - gracia - 1, fecha_vencimiento=add_months(fecha, g),
                          saldo_insoluto=r2(monto), capital_esperado=Decimal(0),
                          interes_ordinario_esperado=Decimal(0), cuota_fija=Decimal(0)))

    cuota_teorica = monto * (i / (1 - (1 + i) ** -plazo)) if i > 0 else monto / plazo
    saldo = monto
    for n in range(1, plazo + 1):
        interes = r2(saldo * tasa / div)  # multiplicar antes de dividir: evita errores en empates de medio centavo
        capital = r2(saldo) if n == plazo else r2(cuota_teorica - interes)
        filas.append(dict(numero_cuota=n, fecha_vencimiento=add_months(fecha, n + gracia),
                          saldo_insoluto=r2(saldo), capital_esperado=capital,
                          interes_ordinario_esperado=interes, cuota_fija=r2(capital + interes)))
        saldo -= capital
    return filas


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--creditos", required=True)
    ap.add_argument("--amortizaciones", required=True)
    ap.add_argument("--salida", required=True)
    ap.add_argument("--tasa", choices=("anual", "mensual"), default="anual")
    a = ap.parse_args()

    with open(a.creditos, newline="", encoding="utf-8") as f:
        creditos = {r["credito_id"]: r for r in csv.DictReader(f)}
    guardadas = defaultdict(dict)
    with open(a.amortizaciones, newline="", encoding="utf-8") as f:
        for r in csv.DictReader(f):
            guardadas[r["credito_id"]][int(r["numero_cuota"])] = r

    difs = []
    malos = {}
    por_rango = Counter()
    estructura = []
    cuotas = 0

    for cid, cr in creditos.items():
        esperado = tabla(cr, a.tasa)
        real = guardadas.get(cid, {})
        nums_esp = {f["numero_cuota"] for f in esperado}
        for faltante in sorted(nums_esp - set(real)):
            estructura.append((cid, cr["modalidad"], faltante, "fila ausente"))
        for sobrante in sorted(set(real) - nums_esp):
            estructura.append((cid, cr["modalidad"], sobrante, "fila no esperada"))

        for f in esperado:
            g = real.get(f["numero_cuota"])
            if g is None:
                continue
            cuotas += 1
            base_fila = dict(credito_id=cid, modalidad=cr["modalidad"], plazo=cr["plazo_meses"],
                             numero_cuota=f["numero_cuota"], estado=g["estado"],
                             capital_pagado=g["capital_pagado"])
            if date.fromisoformat(g["fecha_vencimiento"]) != f["fecha_vencimiento"]:
                difs.append(dict(base_fila, campo="fecha_vencimiento", guardado=g["fecha_vencimiento"],
                                 recalculado=f["fecha_vencimiento"], diferencia="",
                                 rango="fecha", explicacion=""))
                por_rango[("fecha_vencimiento", "distinta")] += 1
                malos[cid] = cr
            for campo in CAMPOS:
                guard = Decimal(g[campo])
                recal = f[campo]
                exp = ""
                dif = guard - recal
                if campo == "saldo_insoluto" and dif != 0:
                    # el pago descuenta capital_pagado del saldo guardado
                    if guard + Decimal(g["capital_pagado"]) == recal:
                        exp = "saldo descontado por capital pagado"
                elif campo == "interes_ordinario_esperado" and dif != 0:
                    if guard == Decimal(g["interes_ordinario_pagado"]) and g["estado"] != "Pendiente":
                        exp = "interes recortado por liquidacion anticipada"
                rango = "explicada por pago" if exp else clasificar(dif)
                por_rango[(campo, rango)] += 1
                if rango not in ("exacta", "explicada por pago"):
                    malos[cid] = cr
                if rango != "exacta":
                    difs.append(dict(base_fila, campo=campo, guardado=guard, recalculado=recal,
                                     diferencia=dif, rango=rango, explicacion=exp))
        if any(e[0] == cid for e in estructura):
            malos[cid] = cr

    for cid, modalidad, num, motivo in estructura:
        difs.append(dict(credito_id=cid, modalidad=modalidad, plazo=creditos[cid]["plazo_meses"],
                         numero_cuota=num, campo="estructura", guardado="", recalculado="",
                         diferencia="", rango=motivo, estado="", capital_pagado="", explicacion=""))

    cols = ["credito_id", "modalidad", "plazo", "numero_cuota", "campo", "guardado", "recalculado",
            "diferencia", "rango", "estado", "capital_pagado", "explicacion"]
    with open(a.salida, "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=cols)
        w.writeheader()
        w.writerows(difs)

    print(f"Interpretacion de tasa: {a.tasa}")
    print(f"Creditos: {len(creditos)} | cuotas comparadas: {cuotas}")
    print(f"Creditos con diferencias reales o de estructura: {len(malos)}")
    print(f"Creditos coincidentes: {len(creditos) - len(malos)}")
    print("\nDiferencias por campo y rango (cuotas):")
    for (campo, rango), n in sorted(por_rango.items()):
        print(f"  {campo:28s} {rango:28s} {n}")
    if estructura:
        print("\nEstructura:")
        for e in estructura:
            print("  credito %s (%s) cuota %s: %s" % e)
    pat = defaultdict(lambda: [0, 0])
    for cid, cr in creditos.items():
        pat[cr["modalidad"]][0] += 1
        pat[cr["modalidad"]][1] += cid in malos
    print("\nPor modalidad (creditos, con diferencias):")
    for k, (t, m) in sorted(pat.items()):
        print(f"  {k:15s} {t:4d} {m:4d}")
    print("\nCreditos con diferencias:", ", ".join(sorted(malos, key=int)))


if __name__ == "__main__":
    main()
