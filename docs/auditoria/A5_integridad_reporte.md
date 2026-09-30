# A.5 · Integridad de la tabla de amortización

---

## Resumen

Comprobé que la tabla de amortización de cada crédito cuadre con su propio contrato: capital, plazo, saldo insoluto y cuota fija. Las consultas corrieron el 30/09/2026 sobre datos sembrados el 21/09/2026.

| Comprobación | Base local (datos de prueba) |
|---|---|
| Créditos revisados | 35 (455 cuotas) |
| Capital en plan = monto otorgado | 35 pasan, 0 fallan |
| Cuotas = plazo | 35 pasan, 0 fallan |
| Cuotas de gracia de Sustentable | 0 pasan, 9 fallan |
| Saldo insoluto descendente y en cero | 35 pasan, 0 fallan |
| Cuota fija constante | 35 pasan, 0 fallan |
| Fallan más de una comprobación | 0 |

Los 9 créditos Sustentable no tienen ninguna de las 3 filas de gracia que el motor genera. Fallan una sola comprobación y la causa está en los seeders, no en el motor. Además, `saldo_insoluto` guarda dos cosas distintas según la cuota esté pagada o no, y eso produce falsos positivos en cualquier revisión directa.

---

## Capital contra monto otorgado

Sumé `capital_esperado` por crédito y lo comparé con `monto_otorgado` (consultas 1 y 2 de [`A5_integridad.sql`](sql/A5_integridad.sql)).

| Diferencia | Créditos |
|---|---|
| 0.00 | 35 |
| Hasta 0.01 (redondeo) | 0 |
| Más de 0.01 (error) | 0 |
| Sin plan de amortización | 0 |

Tomo 0.01 como límite entre redondeo y error. El motor hace que la última cuota absorba el residuo ([`CreditService.php:53`](../../app/Services/CreditService.php#L53)), así que la suma debería salir exacta. Una diferencia de un centavo ya indicaría que el plan se modificó después de generarse; una de pesos, que se generó mal.

---

## Cuotas contra plazo y gracia

Conté las cuotas con `estado <> 'Gracia'` contra `plazo_meses`, y las filas de gracia contra las 3 que el motor genera para Sustentable ([`CreditService.php:28-41`](../../app/Services/CreditService.php#L28)). Consultas 3 y 4.

| Modalidad | Créditos | Cuotas = plazo | Gracia correcta | Meses de entrega a cuota 1 |
|---|---|---|---|---|
| Artesanal | 8 | 8 | 8 | 1 |
| Emprendedores | 18 | 18 | 18 | 1 |
| Sustentable | 9 | 9 | 0 | 4 |

Los 9 afectados, por id: 3, 14, 15, 16, 17, 18, 28, 30 y 32 (claves CREA-2024-003, -007, -011, -015, -023, -031, -019, -027 y -035).

Su primera cuota vence 4 meses después de la entrega, lo que refleja los 3 meses de prórroga, pero no hay filas que los representen. Los seeders arman la tabla por su cuenta y no llaman al motor: [`TestCreditosSeeder.php:202`](../../database/seeders/TestCreditosSeeder.php#L202) y [`Add30CreditosSeeder.php:199`](../../database/seeders/Add30CreditosSeeder.php#L199) desplazan el vencimiento, pero no crean la gracia.

---

## Saldo insoluto

El motor guarda en `saldo_insoluto` el saldo antes de pagar la cuota. Al registrar un pago, [`PagoController.php:239-246`](../../app/Http/Controllers/PagoController.php#L239) lo sobrescribe con el saldo después de pagar; el abono anticipado hace lo mismo en [`PagoController.php:375-386`](../../app/Http/Controllers/PagoController.php#L375).

Comparar cada cuota con la anterior da un resultado que no corresponde a la realidad (consulta 5):

| Comprobación | Créditos que fallan |
|---|---|
| Alguna cuota con saldo igual o mayor al de la anterior | 22 |
| Saldo de la última cuota menos su capital distinto de cero | 4 |
| Reconstruyendo el saldo desde el monto, descontando `capital_pagado` (consulta 6) | 0 |

Ejemplo: en el crédito 7, la cuota 7 (`Pagado`) y la cuota 8 (`Pendiente`) tienen `saldo_insoluto = 43,356.55`. La 7 guarda el saldo después de su pago y la 8, el saldo antes del suyo.

Los 4 con saldo final distinto de cero son los `Liquidado` (créditos 4, 33, 34 y 35): su última cuota ya está pagada, guarda 0 y al restarle el capital sale negativo, entre -1,000.00 y -2,580.77.

Con el saldo reconstruido, los 35 créditos coinciden en todas sus filas, con diferencia máxima de 0.00, y terminan en cero. Incluye el crédito 5, que tiene una cuota `Parcial`.

---

## Cuota fija

Contando la última cuota, 25 de los 35 créditos tienen más de un valor de `cuota_fija`. Sin contarla, 0 (consultas 7 y 8).

| Cuota | Créditos con más de un valor |
|---|---|
| Todas las cuotas | 25 |
| Sin la última | 0 |

La última cuota difiere de la cuota 1 entre -0.10 y +0.13 pesos: es el residuo de redondeo que absorbe el capital. La tabla `reestructuraciones` está vacía en mi base, así que no hay créditos con dos valores que cruzar.

---

## Créditos que fallan más de una comprobación

Ninguno (consulta 9). Los 9 créditos con la gracia faltante fallan únicamente esa comprobación.

---

## Lo que no pude determinar

- Si los créditos Sustentable dados de alta desde [`AcreditadoController.php`](../../app/Http/Controllers/AcreditadoController.php) y [`SolicitudOperativoController.php`](../../app/Http/Controllers/SolicitudOperativoController.php) tienen sus 3 filas de gracia. Pasan por el motor y deberían tenerlas.
- El comportamiento de créditos reestructurados: no hay ninguno en la base local.

---

## Recomendación

Que los seeders llamen a `CreditService` en lugar de repetir su lógica, para que los datos de prueba tengan gracia y las comprobaciones de este reporte se puedan probar contra Sustentable. Sobre `saldo_insoluto`, la decisión de si se separa en dos columnas o se documenta el doble significado es de Diego.


