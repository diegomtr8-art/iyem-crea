# A.10 · La mora calculada de tres formas

\---

## Qué dice el Acuerdo

Acuerdo IYEM 03/2025, Diario Oficial del Estado de Yucatán del 31/10/2025, Anexo 7 (modelo de contrato), cláusula séptima, «Intereses moratorios», página 84:

> En caso de que "EL ACREDITADO" no pague puntualmente alguna cantidad que debe cubrir en favor de "EL INSTITUTO" conforme al presente contrato, exceptuando intereses, dicha cantidad devengará intereses moratorios desde la fecha de su vencimiento hasta que se pague totalmente, los cuales se devengarán diariamente, se pagarán a la vista y conforme a una tasa anualizada igual al resultado de multiplicar la tasa de interés ordinaria por 2.5 (dos punto cinco). Para calcular los intereses moratorios, la tasa anualizada de interés moratorio aplicable se dividirá entre 360 (trescientos sesenta) y el cociente se aplicará a los saldos insolutos y vencidos, resultando así el interés moratorio de cada día \[…]
>
> Todos los acreditados tendrán cinco días naturales de gracia contados a partir de su fecha de pago indicada en su tabla de amortización, en caso de no realizar pago dentro de este periodo el pago se cobrará con los moratorios generados a partir del primer día de atraso.

|Regla|Acuerdo|
|-|-|
|Base|La cantidad vencida no pagada, «exceptuando intereses»: el capital vencido de la cuota|
|Tasa|Ordinaria × 2.5|
|Gracia|5 días naturales; pasados, se cobra desde el día 1|
|Año|360 días|

\---

## Monto por escenario y fórmula

Créditos de prueba en mi base local, creados con el motor real: $20,000 a 12 meses, 7% ordinario, 17.5% moratorio. Medidos el 05/10/2026.

|Escenario|Atraso|Cuota|`CreditService` (cuota restante)|Cron (saldo − capital pagado)|Portal (saldo insoluto)|Acuerdo (capital vencido)|
|-|-|-|-|-|-|-|
|E1|3 días|12, pendiente|$0.00|$0.00|$0.00|$0.00|
|E2|6 días|12, pendiente|$5.05|$5.02|$5.02|$5.02|
|E3|30 días|12, pendiente|$25.24|$25.09|$25.09|$25.09|
|E4|90 días|12, pendiente|$75.71|$75.27|$75.27|$75.27|
|E5|30 días|12, parcial|$10.65|$0.00|$10.65|$10.65|
|E6|30 días|1, con 11 posteriores|$25.24|$291.67|$291.67|$23.54|

Diferencia contra el Acuerdo:

|Escenario|`CreditService`|Cron|Portal|
|-|-|-|-|
|E1|$0.00|$0.00|$0.00|
|E2|+$0.03|$0.00|$0.00|
|E3|+$0.15|$0.00|$0.00|
|E4|+$0.44|$0.00|$0.00|
|E5|$0.00|−$10.65|$0.00|
|E6|+$1.70|+$268.13|+$268.13|

Los montos medidos en el sistema coinciden al centavo con los de [`recalculo\_mora.py`](../../tools/auditoria/recalculo_mora.py).

\---

## E6 desarrollado día por día

Cuota 1: capital $1,613.86, interés $116.67, cuota $1,730.53. Saldo insoluto $20,000.00. Tasa diaria: 17.5 / 100 / 360 = 0.000486111.

||Acuerdo|Cron|
|-|-|-|
|Base|$1,613.86|$20,000.00|
|Mora por día|$0.784515|$9.722222|

|Día|Acuerdo acumulada|Acuerdo cobrable|Cron acumulada|Cron cobrable|
|-|-|-|-|-|
|1|0.784515|$0.00|9.722222|$0.00|
|2|1.569031|$0.00|19.444444|$0.00|
|3|2.353546|$0.00|29.166667|$0.00|
|4|3.138061|$0.00|38.888889|$0.00|
|5|3.922576|$0.00|48.611111|$0.00|
|6|4.707092|$4.71|58.333333|$58.33|
|7|5.491607|$5.49|68.055556|$68.06|
|8|6.276122|$6.28|77.777778|$77.78|
|9|7.060638|$7.06|87.500000|$87.50|
|10|7.845153|$7.85|97.222222|$97.22|
|11|8.629668|$8.63|106.944444|$106.94|
|12|9.414183|$9.41|116.666667|$116.67|
|13|10.198699|$10.20|126.388889|$126.39|
|14|10.983214|$10.98|136.111111|$136.11|
|15|11.767729|$11.77|145.833333|$145.83|
|16|12.552244|$12.55|155.555556|$155.56|
|17|13.336760|$13.34|165.277778|$165.28|
|18|14.121275|$14.12|175.000000|$175.00|
|19|14.905790|$14.91|184.722222|$184.72|
|20|15.690306|$15.69|194.444444|$194.44|
|21|16.474821|$16.47|204.166667|$204.17|
|22|17.259336|$17.26|213.888889|$213.89|
|23|18.043851|$18.04|223.611111|$223.61|
|24|18.828367|$18.83|233.333333|$233.33|
|25|19.612882|$19.61|243.055556|$243.06|
|26|20.397397|$20.40|252.777778|$252.78|
|27|21.181912|$21.18|262.500000|$262.50|
|28|21.966428|$21.97|272.222222|$272.22|
|29|22.750943|$22.75|281.944444|$281.94|
|30|23.535458|$23.54|291.666667|$291.67|

\---

## Cuál coincide con el Acuerdo

|Fórmula|Dónde|¿Coincide?|Diferencia|
|-|-|-|-|
|Cuota restante|[`CreditService.php:93`](../../app/Services/CreditService.php#L93)|No: incluye el interés de la cuota|+$0.03 a +$1.70|
|Saldo − capital pagado|[`UpdateMoratorio.php:33-34`](../../app/Console/Commands/UpdateMoratorio.php#L33)|No: incluye el capital de las cuotas que aún no vencen y resta dos veces los abonos|−$10.65 (E5) a +$268.13 (E6)|
|Saldo insoluto|[`MiCreditoController.php:43`](../../app/Http/Controllers/Portal/MiCreditoController.php#L43)|No: incluye el capital de las cuotas que aún no vencen|+$268.13 (E6)|

Ninguna coincide en todos los escenarios. Para una cuota de $1,730.53 con 30 días de atraso (E6), el cron cobra $291.67 y el Acuerdo da $23.54. El cron y el portal coinciden con el Acuerdo en E2 a E4 solo porque es la última cuota, donde el saldo insoluto es igual al capital de la cuota.

\---

## Qué se cobra y qué se ve hoy

`PagoController` no lee la mora de ninguna columna: la calcula al momento con la fórmula del cron, `max(0, saldo\_insoluto − capital\_pagado)`, en la pantalla de cobro ([`PagoController.php:40-41`](../../app/Http/Controllers/PagoController.php#L40)) y al registrar el pago ([`PagoController.php:214-215`](../../app/Http/Controllers/PagoController.php#L214)).

|Escenario|Caja cobra (mora / total)|Ciudadano ve en «Mi crédito» (mora / total)|
|-|-|-|
|E1|$0.00 / $1,730.58|$0.00 / $1,730.58|
|E2|$5.02 / $1,735.60|$5.02 / $1,735.60|
|E3|$25.09 / $1,755.67|$25.09 / $1,755.67|
|E4|$75.27 / $1,805.85|$75.27 / $1,805.85|
|E5|$0.00 / $730.58|$10.65 / $741.23|
|E6|$291.67 / $2,022.20|$291.67 / $2,022.20|

En E5 el ciudadano ve $10.65 más de lo que le cobran en caja. Al registrar un abono, [`PagoController.php:239`](../../app/Http/Controllers/PagoController.php#L239) le resta el capital a `saldo\_insoluto` y [la línea 242](../../app/Http/Controllers/PagoController.php#L242) lo suma a `capital\_pagado`, y la fórmula del cron vuelve a restarlo: $730.58 − $989.96 queda en $0.

\---

## Lo que no pude determinar

* Si «saldos insolutos y vencidos» incluye el interés de la cuota. Si lo incluye, la base del Acuerdo es la de `CreditService` y la diferencia en E6 baja de $1.70 a $0.

