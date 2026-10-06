#!/usr/bin/env python3
"""Recalcula las reestructuraciones de CREA y las compara con lo guardado (solo lee TSV).

No usa el código del sistema. Toma la tabla de cada crédito antes de reestructurar, las
condiciones capturadas (fecha, plazo, tasa, inicio de pagos) y la tabla y el registro
después, y recalcula base, mora y tabla nueva con Decimal.

python recalculo_reestructuracion.py --datos docs/auditoria/A11_datos [--salida d.csv] [--detalle 43]

En --datos: creditos.tsv, amortizaciones_antes.tsv, amortizaciones_despues.tsv,
reestructuraciones.tsv (exportados con mysql -B).
"""
import argparse
import calendar
import csv
from collections import Counter, defaultdict
from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP, getcontext
from pathlib import Path

getcontext().prec = 40
CENTAVO = Decimal("0.01")
CERO = Decimal(0)
CAMPOS = ("cuota_fija", "capital_esperado", "interes_ordinario_esperado", "saldo_insoluto")
CERRADAS = {"Pagado", "Condonado", "Reestructurada", "Gracia"}
DIAS_ANIO = 360
DIAS_GRACIA_MORA = 5


def r2(x):
    return x.quantize(CENTAVO, rounding=ROUND_HALF_UP)


def d(v):
    return Decimal(v) if v not in ("", "NULL", None) else CERO


def add_months(f, n):
    """Como Carbon::addMonths, con desborde de fin de mes."""
    m = f.month - 1 + n
    y, m = f.year + m // 12, m % 12 + 1
    dim = calendar.monthrange(y, m)[1]
    if f.day <= dim:
        return date(y, m, f.day)
    return date(y, m, dim) + timedelta(days=f.day - dim)


def clasificar(dif):
    a = abs(dif)
    if a == 0:
        return "exacta"
    if a <= CENTAVO:
        return "redondeo (<= 0.01)"
    if a <= Decimal("1.00"):
        return "sospechosa (0.02-1.00)"
    return "error (> 1.00)"


def leer(ruta):
    with open(ruta, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def tabla_original(cr):
    """Tabla del crédito según el contrato (sin gracia), para no fiarse del interés guardado."""
    monto = d(cr["monto_otorgado"])
    plazo = int(cr["plazo_meses"])
    tasa = d(cr["tasa_interes_ordinario"])
    i = tasa / 1200
    cuota = monto * (i / (1 - (1 + i) ** -plazo)) if i > 0 else monto / plazo
    saldo, filas = monto, {}
    for n in range(1, plazo + 1):
        interes = r2(saldo * tasa / 1200)
        capital = r2(saldo) if n == plazo else r2(cuota - interes)
        filas[n] = dict(capital=capital, interes=interes)
        saldo -= capital
    return filas


def base_y_mora(cr, cuotas, fecha):
    """Base = capital pendiente + interés ordinario de cuotas vencidas a `fecha`, menos lo pagado."""
    contrato = tabla_original(cr)
    abiertas = [c for c in cuotas if c["estado"] not in CERRADAS]
    # Capital pendiente por dos caminos: monto menos todo lo pagado, y suma de lo no pagado.
    cap_por_monto = d(cr["monto_otorgado"]) - sum(d(c["capital_pagado"]) for c in cuotas)
    cap_por_cuotas = sum(d(c["capital_esperado"]) - d(c["capital_pagado"]) for c in abiertas)

    devengado = CERO
    interes_contrato_distinto = []
    for c in abiertas:
        if date.fromisoformat(c["fecha_vencimiento"]) > fecha:
            continue
        n = int(c["numero_cuota"])
        esperado = contrato[n]["interes"] if n in contrato else d(c["interes_ordinario_esperado"])
        if esperado != d(c["interes_ordinario_esperado"]):
            interes_contrato_distinto.append(n)
        devengado += max(CERO, esperado - d(c["interes_ordinario_pagado"]))

    # Mora a la fecha de reestructura, con la base del Acuerdo (capital vencido de la cuota)
    # y con la del cron (saldo insoluto − capital pagado). Año de 360, gracia de 5 días.
    tasa_diaria = d(cr["tasa_interes_moratorio"]) / 100 / DIAS_ANIO
    mora_acuerdo = mora_cron = CERO
    detalle_mora = []
    for c in abiertas:
        dias = (fecha - date.fromisoformat(c["fecha_vencimiento"])).days
        if dias <= DIAS_GRACIA_MORA:
            continue
        b_acuerdo = max(CERO, d(c["capital_esperado"]) - d(c["capital_pagado"]))
        b_cron = r2(max(CERO, d(c["saldo_insoluto"]) - d(c["capital_pagado"])))
        pagada = d(c["interes_moratorio_pagado"])
        m_a = max(CERO, r2(b_acuerdo * tasa_diaria * dias) - pagada)
        m_c = max(CERO, r2(b_cron * tasa_diaria * dias) - pagada)
        mora_acuerdo += m_a
        mora_cron += m_c
        detalle_mora.append((int(c["numero_cuota"]), dias, b_acuerdo, m_a, b_cron, m_c))

    mora_guardada = sum(max(CERO, d(c["interes_moratorio_generado"]) - d(c["interes_moratorio_pagado"]))
                        for c in abiertas)
    return dict(capital=cap_por_monto, capital_cuotas=cap_por_cuotas, devengado=devengado,
                base=r2(cap_por_monto + devengado), mora_acuerdo=mora_acuerdo, mora_cron=mora_cron,
                mora_guardada=mora_guardada, detalle_mora=detalle_mora,
                interes_contrato_distinto=interes_contrato_distinto)


def tabla_nueva(base, plazo, tasa, inicio, primer_numero):
    """Tabla francesa sobre la base. La cuota 1 vence en la fecha de inicio de pagos."""
    i = tasa / 1200
    cuota = base * (i / (1 - (1 + i) ** -plazo)) if i > 0 else base / plazo
    saldo, filas = base, []
    for k in range(1, plazo + 1):
        interes = r2(saldo * tasa / 1200)  # multiplicar antes de dividir
        capital = r2(saldo) if k == plazo else r2(cuota - interes)
        filas.append(dict(numero_cuota=primer_numero + k - 1, fecha_vencimiento=add_months(inicio, k - 1),
                          saldo_insoluto=r2(saldo), capital_esperado=capital,
                          interes_ordinario_esperado=interes, cuota_fija=r2(capital + interes)))
        saldo -= capital
    return filas, cuota


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--datos", required=True)
    ap.add_argument("--salida")
    ap.add_argument("--detalle", help="credito_id a desarrollar cuota por cuota")
    a = ap.parse_args()
    p = Path(a.datos)

    creditos = {r["credito_id"]: r for r in leer(p / "creditos.tsv")}
    antes, despues = defaultdict(list), defaultdict(list)
    for r in leer(p / "amortizaciones_antes.tsv"):
        antes[r["credito_id"]].append(r)
    for r in leer(p / "amortizaciones_despues.tsv"):
        despues[r["credito_id"]].append(r)
    reestructuras = leer(p / "reestructuraciones.tsv")

    difs, por_rango, malos = [], Counter(), set()
    puntos = []

    def dif(cid, num, campo, guard, recal, rango=None, nota=""):
        rango = rango or clasificar(guard - recal)
        por_rango[(campo, rango)] += 1
        if rango != "exacta":
            malos.add(cid)
            difs.append(dict(credito_id=cid, numero_cuota=num, campo=campo, guardado=guard,
                             recalculado=recal, diferencia=guard - recal if rango != "fecha" else "",
                             rango=rango, nota=nota))

    for rs in reestructuras:
        cid = rs["credito_id"]
        cr = creditos[cid]
        fecha = date.fromisoformat(rs["fecha_reestructura"])
        plazo = int(rs["nuevo_plazo_meses"])
        tasa = d(rs["nueva_tasa_interes"])
        inicio = date.fromisoformat(rs["nueva_fecha_inicio_pagos"])
        previas = antes[cid]
        ultima = max(int(c["numero_cuota"]) for c in previas)

        b = base_y_mora(cr, previas, fecha)
        dif(cid, "", "base (saldo_al_momento)", d(rs["saldo_al_momento"]), b["base"])
        dif(cid, "", "mora_condonada vs mora guardada", d(rs["mora_condonada"]), r2(b["mora_guardada"]))
        dif(cid, "", "mora_condonada vs cron a fecha", d(rs["mora_condonada"]), r2(b["mora_cron"]))
        dif(cid, "", "mora_condonada vs Acuerdo a fecha", d(rs["mora_condonada"]), r2(b["mora_acuerdo"]))

        esperada, _ = tabla_nueva(b["base"], plazo, tasa, inicio, ultima + 1)
        guardada = {int(c["numero_cuota"]): c for c in despues[cid]}
        nuevas = sorted(n for n, c in guardada.items() if n > ultima)

        for f in esperada:
            g = guardada.get(f["numero_cuota"])
            if g is None:
                dif(cid, f["numero_cuota"], "estructura", "", "", "fila ausente")
                continue
            for campo in CAMPOS:
                dif(cid, f["numero_cuota"], campo, d(g[campo]), f[campo])
            if date.fromisoformat(g["fecha_vencimiento"]) != f["fecha_vencimiento"]:
                dif(cid, f["numero_cuota"], "fecha_vencimiento", g["fecha_vencimiento"],
                    f["fecha_vencimiento"].isoformat(), "fecha")

        # Paso 6 de la tarea, medido.
        primera = guardada[ultima + 1] if ultima + 1 in guardada else None
        f1 = date.fromisoformat(primera["fecha_vencimiento"]) if primera else None
        abiertas_antes = {int(c["numero_cuota"]) for c in previas if c["estado"] not in CERRADAS}
        viejas_mal = [n for n in abiertas_antes
                      if guardada[n]["estado"] != "Reestructurada" or d(guardada[n]["pago_restante"]) != 0]
        cerradas_tocadas = [int(c["numero_cuota"]) for c in previas
                            if c["estado"] in CERRADAS and any(c[k] != guardada[int(c["numero_cuota"])][k]
                                                               for k in ("estado", "pago_restante", "capital_pagado"))]
        suma_cap = sum(d(guardada[n]["capital_esperado"]) for n in nuevas)
        puntos.append(dict(
            credito_id=cid, clave=cr["clave_contrato"], modalidad=cr["modalidad"],
            fecha_reestructura=fecha, inicio=inicio, primera_vence=f1,
            desfase_dias=(f1 - inicio).days if f1 else None,
            ultima_vieja=ultima, nuevas=f"{nuevas[0]}-{nuevas[-1]}" if nuevas else "-",
            consecutiva=nuevas == list(range(ultima + 1, ultima + plazo + 1)),
            reestructuradas=len(abiertas_antes), viejas_mal=viejas_mal, cerradas_tocadas=cerradas_tocadas,
            suma_capital=suma_cap, base=d(rs["saldo_al_momento"]), base_recalc=b["base"],
            capital=b["capital"], capital_cuotas=b["capital_cuotas"], devengado=b["devengado"],
            mora_guardada=r2(b["mora_guardada"]), mora_cron=r2(b["mora_cron"]),
            mora_acuerdo=r2(b["mora_acuerdo"]), mora_condonada=d(rs["mora_condonada"]),
            interes_contrato_distinto=b["interes_contrato_distinto"]))

        if a.detalle == cid:
            print(f"\n=== Crédito {cid} ({cr['clave_contrato']}) desarrollado ===")
            print(f"Capital pendiente = monto − capital pagado = {b['capital']} "
                  f"(por cuotas abiertas: {b['capital_cuotas']})")
            print(f"Interés devengado a {fecha} = {b['devengado']}  ->  base = {b['base']}")
            print("Mora por cuota (n, días, base Acuerdo, mora Acuerdo, base cron, mora cron):")
            for x in b["detalle_mora"]:
                print("  ", *x)
            _, ct = tabla_nueva(b["base"], plazo, tasa, inicio, ultima + 1)
            print(f"Cuota teórica = {ct:.6f}")
            print("n | vence (esperado) | vence (sistema) | saldo | interés | capital | cuota | sistema cuota/capital/interés/saldo")
            for f in esperada:
                g = guardada.get(f["numero_cuota"], {})
                print(f["numero_cuota"], f["fecha_vencimiento"], g.get("fecha_vencimiento"), f["saldo_insoluto"],
                      f["interes_ordinario_esperado"], f["capital_esperado"], f["cuota_fija"],
                      g.get("cuota_fija"), g.get("capital_esperado"), g.get("interes_ordinario_esperado"),
                      g.get("saldo_insoluto"), sep=" | ")

    if a.salida:
        with open(a.salida, "w", newline="", encoding="utf-8") as f:
            w = csv.DictWriter(f, fieldnames=["credito_id", "numero_cuota", "campo", "guardado",
                                              "recalculado", "diferencia", "rango", "nota"])
            w.writeheader()
            w.writerows(difs)

    print(f"\nReestructuraciones: {len(reestructuras)}")
    print("\nDiferencias por campo y rango:")
    for (campo, rango), n in sorted(por_rango.items()):
        print(f"  {campo:36s} {rango:24s} {n}")
    print("\nPaso 6 y base por crédito:")
    for x in puntos:
        print(f"  {x['credito_id']:>3} {x['clave']:13s} {x['modalidad']:13s} "
              f"base sis {x['base']} / recalc {x['base_recalc']} (cap {x['capital']} = {x['capital_cuotas']}, dev {x['devengado']}) | "
              f"inicio {x['inicio']} -> 1a vence {x['primera_vence']} ({x['desfase_dias']} d) | "
              f"viejas hasta {x['ultima_vieja']}, nuevas {x['nuevas']} consecutiva={x['consecutiva']} | "
              f"reestructuradas {x['reestructuradas']} mal={x['viejas_mal']} cerradas tocadas={x['cerradas_tocadas']} | "
              f"Σcapital {x['suma_capital']} | mora condonada {x['mora_condonada']} guardada {x['mora_guardada']} "
              f"cron@fecha {x['mora_cron']} Acuerdo@fecha {x['mora_acuerdo']}"
              + (f" | interés de contrato distinto en {x['interes_contrato_distinto']}" if x['interes_contrato_distinto'] else ""))


if __name__ == "__main__":
    main()
