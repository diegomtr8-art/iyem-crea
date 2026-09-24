# A.2 · Conciliación entre `pagos` y `amortizaciones`


---

## Resumen

Este reporte no mide el daño en producción. No tuve acceso a una copia de esa base, así que las cifras que siguen salen de mi base local, cargada con los seeders de prueba.

| | Base local (datos de prueba) | Producción |
|---|---|---|
| Créditos revisados | 35 (27 con pagos vigentes) | No revisado |
| Créditos descuadrados | 0 | No verificado |
| Diferencia total | $0.00 en los cuatro conceptos | No verificado |

Ese cero no sirve como respuesta. Los seeders insertan los pagos directamente en la base y nunca pasan por el código de caja, que es justo donde está el problema.

Donde sí pude avanzar fue en el código y en el historial de git. Encontré tres cosas:

1. «Reducir Cuota» descuadra, pero solo con el código que hubo en `develop` entre el 7 y el 21 de septiembre de 2026. En esas dos semanas el sobrante se sumaba a `pagos.aplicado_capital` y no llegaba a `amortizaciones.capital_pagado`. «Reducir Plazo» fallaba igual.
2. En `main` ese camino nunca se ejecuta. No sé cuál de las dos versiones estuvo en producción durante esas semanas, y el tamaño del daño depende por completo de eso.
3. El arreglo de hoy (`210f915`) corrige el capital, pero en «Reducir Plazo» abre un descuadre nuevo, ahora en el interés ordinario.

Las consultas del archivo SQL están listas para correrse en producción. Con ellas se obtienen las cifras que aquí faltan.

---

## Grupos

En los datos que tuve no apareció ningún descuadre, así que no hay grupos observados. Lo que sigue son los caminos del código que pueden producir uno. Dejé sin llenar las columnas de créditos y dinero porque no las pude medir.

| Grupo | Créditos | Dinero | Causa probable | Dirección | Cómo detectarlo |
|---|---|---|---|---|---|
| A. Reducir Cuota sin registrar el abono | No verificado | No verificado | Del 7 al 21 de sep, el sobrante entraba a `aplicado_capital`, pero el plan se rehacía bajando `capital_esperado` sin tocar `capital_pagado` | Pagos > amortizaciones (capital) | Consultas 1 y 5 |
| B. Reducir Plazo con cuotas "eliminadas" | No verificado | No verificado | Del 8 al 21 de sep, las últimas cuotas quedaban en `Pagado` con `capital_esperado = 0` y el dinero no se registraba | Pagos > amortizaciones (capital) | Consultas 1, 5 y 6 (marca "Cuota eliminada por reducción de plazo") |
| C. Reducir Plazo con interés marcado sin cobrar | No verificado | No verificado | El 7 y 8 de sep (hasta `d84b55c`) y otra vez desde `210f915`, las cuotas liquidadas por abono quedan con `interes_ordinario_pagado` igual al esperado, aunque ese interés no entró por caja | Amortizaciones > pagos (interés ordinario) | Consultas 2 y 6 (marca "Liquidada por abono anticipado a capital") |
| D. Comisiones | No verificable | No verificable | `pagos` no tiene columna de comisión y el código nunca escribe `comision_pagada` | Solo puede salir amortizaciones > 0 | Consulta 4 |

El ticket mencionaba otros dos grupos posibles: diferencias de centavos por redondeo y pagos sin amortizaciones tocadas. No los incluí. Pueden existir, pero no encontré en el código nada que los provoque, y sin datos no tengo cómo sostenerlos.

---

## Casos desarrollados

No tengo casos reales. Para cada grupo describo con números sencillos lo que hace el código. Son ejemplos teóricos: ninguno corresponde a un crédito existente.

### Grupo A · Reducir Cuota (7 al 21 de sep)

La cuota corriente de un crédito es de $1,000: $900 de capital y $100 de interés. El acreditado paga $1,500 y el operativo elige «Reducir Cuota».

Los primeros $1,000 cubren la cuota corriente. En `cuotas_cubiertas` queda `cap: 900` y la cuota suma $900 a `capital_pagado`. Hasta ahí todo cuadra.

Los $500 que sobran se van a `aplicarAbonoCapital`, que rehace las cuotas restantes y les baja $500 de `capital_esperado` en total. A `capital_pagado` no les suma nada. Mientras tanto, `pagos` guarda `aplicado_capital = 1,400`.

Caja dice $1,400 y las cuotas dicen $900. La diferencia, $500, es exactamente el sobrante. El acreditado sí ve bajar su saldo, porque bajó lo que se espera que pague, pero ninguna cuota registra ese dinero como recibido.

### Grupo B · Reducir Plazo (8 al 21 de sep)

Mismo pago, pero ahora el operativo elige «Reducir Plazo». El sobrante acorta el plan: las últimas cuotas pasan a `Pagado`, con `capital_esperado = 0` y la observación "Cuota eliminada por reducción de plazo". Tampoco aquí se anota nada en `capital_pagado`. El efecto es el mismo que en el grupo A.

### Grupo C · Reducir Plazo con interés marcado sin cobrar (7 y 8 de sep, y desde `210f915`)

En estas versiones el capital sí cuadra, porque el abono se anota en `capital_pagado`. El problema está en otra columna. Cuando el abono cubre una cuota del final, el código también le pone `interes_ordinario_pagado = interes_ordinario_esperado`, y ese interés nunca pasó por caja: `pagos.aplicado_ordinario` no lo incluye.

En interés ordinario, las cuotas terminan diciendo que se cobró más de lo que entró. De los cuatro grupos, este es el que más me preocupa, porque es el único que el código actual de `develop` puede seguir generando.

### Grupo D · Comisiones

Aquí no hay mecanismo que contar. Con la estructura actual no se puede conciliar, porque del lado de `pagos` no existe el dato. Si la consulta 4 regresa filas en producción, esas comisiones llegaron a las cuotas por una importación o por una edición manual.

---

## Lo que no pude determinar

- Cuántos créditos están descuadrados en producción y cuánto dinero representan. Sin copia ni acceso a esa base no hay forma de medirlo.
- Qué versión del código corrió en producción entre el 7 y el 21 de septiembre. `main` está congelada desde el 24 de agosto (`51fbf75`) y ahí el camino peligroso no se ejecuta. Si producción se despliega desde `main`, los grupos A y B podrían estar vacíos. Si en esas fechas se desplegó `develop`, no. El repositorio no guarda registro de despliegues.
- Qué opción se eligió en los pagos del 7 y 8 de septiembre. La columna `tipo_abono` existe desde el 9. La consulta 5 encuentra esos pagos de todos modos, pero no dice si fueron Reducir Cuota o Reducir Plazo.
- Si hay diferencias por redondeo o por datos importados. Es posible, pero no lo vi ni en el código ni en los datos.
- Si alguien editó `amortizaciones` a mano, desde phpMyAdmin o con algún script. Esa tabla no tiene bitácora.

---

## Consultas

Todas las consultas están en [`sql/A2_conciliacion.sql`](sql/A2_conciliacion.sql). Son `SELECT`, así que se pueden correr sin riesgo en phpMyAdmin (pestaña SQL, con la base seleccionada).

| Consulta | Qué responde |
|---|---|
| 1 a 4 | Créditos descuadrados por concepto: capital, interés ordinario, interés moratorio y comisiones |
| 5 | Pagos que mandaron sobrante a capital y cuánto (grupos A y B) |
| 6 | Cuotas tocadas por «Reducir Plazo», separadas por versión del código (grupos B y C) |
| 7 | Las cifras del resumen: créditos revisados, descuadrados y diferencia total por concepto |


