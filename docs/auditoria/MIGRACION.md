# Migración de créditos CREA desde crea-iyem.com

---

## Resumen

Diagnóstico, script e instrucciones para pasar los créditos CREA del sistema viejo a `crea.iyemyucatan.com`. La migración no se ejecuta ahora: se corre cuando el sistema esté listo para producción. Esta entrega deja el procedimiento ensayado.

| Alcance | Valor |
|---|---|
| Créditos | 146 de 390 (`cliente.acreditado_tipo` = `CREA` o `Crea`). Microyuc se descarta. |
| Cuotas | 3,023 filas de `amortizacion`: 146 de desembolso (`mes = 0`) y 2,877 cuotas |
| Pagos | No se migran. Se capturan a mano en el sistema nuevo. |
| Estado de las cuotas | Entran como no pagadas, sin el avance del sistema viejo |
| Tablas `usuario` e `interesados` | No se migran |

El script ya corrió sobre una base ficticia con la misma forma que describe Diego (12 de octubre de 2026). El ensayo con el volcado real está pendiente: el archivo todavía no está en esta máquina.

---

## Mapeo de columnas

`amortizacion` está confirmado por Diego. En `cliente` solo están confirmados `acreditado_tipo`, `tipo_credito` y `estatus`; los demás nombres son supuestos y se ajustan en el mapa del script ([`importar_crea.php:41`](../../tools/migracion/importar_crea.php#L41)) al recibir el volcado. El script se detiene si alguna columna no existe.

| Viejo | Nuevo | Transformación |
|---|---|---|
| `cliente.acreditado_tipo` | (filtro) | `UPPER(TRIM()) = 'CREA'`. No se guarda. |
| `cliente.tipo_credito` | `creditos.modalidad_id` | Emprendedor y Emprendedores → Emprendedores. Artesanal, Sustentable igual. Otra → rechazo. |
| `cliente.estatus` | `creditos.estatus` | Activo → `Activo`. Otro → rechazo. |
| `cliente.nombre` | `acreditados.nombre_completo` | Vacío → rechazo |
| `cliente.curp`, `cliente.rfc` | `acreditados.curp`, `acreditados.rfc` | Mayúsculas. Vacío → `NULL` (las dos columnas son `unique`). Sirven para no duplicar a una persona con dos créditos. |
| `cliente.correo` | `acreditados.correo` | Vacío → `NULL` |
| `cliente.domicilio` | `acreditados.direccion_fiscal` | Vacío → `NULL` |
| `cliente.municipio` | `acreditados.municipio` | Obligatorio en el destino. Vacío → rechazo. |
| `cliente.sexo` | `acreditados.sexo` | Hombre/Masculino/H → `H`, Mujer/Femenino/F → `M`. Una `M` sola es ambigua → `NULL`. |
| `cliente.monto` | `creditos.monto_otorgado` | |
| `cliente.plazo` | `creditos.plazo_meses` | |
| `cliente.fecha_entrega` | `creditos.fecha_entrega` | Se compara con la fecha del `mes = 0` |
| (sin origen) | `creditos.clave_contrato` | `CREA-V-{cliente.id}`, salvo que el volcado traiga una clave de contrato. Es la llave de idempotencia. |
| (sin origen) | `creditos.tasa_interes_ordinario` | La de la modalidad |
| `amortizacion.taza_moratoria` | `creditos.tasa_interes_moratorio` | Se toma por crédito. Si varía entre cuotas o no coincide con la modalidad, va al anexo. |
| `amortizacion.cliente_id` | `amortizaciones.credito_id` | Por el crédito recién creado |
| `amortizacion.mes` | `amortizaciones.numero_cuota` | `mes = 0` no se importa. Las cuotas quedan 1..plazo. |
| `amortizacion.fecha_pago` | `amortizaciones.fecha_vencimiento` | Vacía → rechazo del crédito |
| `amortizacion.abono_capital` | `amortizaciones.capital_esperado` | |
| `amortizacion.interes_ordinario` | `amortizaciones.interes_ordinario_esperado` | |
| `amortizacion.cuota_mensual` | `amortizaciones.cuota_fija` | Tal cual |
| `amortizacion.saldo` | `amortizaciones.saldo_insoluto` | Se corre una fila: el viejo guarda el saldo después de la cuota y el motor, el de antes ([`CreditService.php:59`](../../app/Services/CreditService.php#L59)). La cuota 1 toma el saldo del `mes = 0`. |
| (calculado) | `amortizaciones.pago_restante` | `capital_esperado + interes_ordinario_esperado`, la misma fórmula del motor ([`PagoController.php:246`](../../app/Http/Controllers/PagoController.php#L246)) |
| (fijo) | `estado`, `*_pagado`, `fecha_ultimo_pago` | `Pendiente`, 0, `NULL` |

No se importan, por decisión de Diego: `fecha_pago_cliente`, `pago_realizado`, `pago_restante`, `pago_parcial`, `pago_moratorios`, `abono`, `abono_capt`, `total_pago`, `total_deuda`, `capital`, `capital_pago`, `ordinario`, `ordinario_pago`, `moratorio`, `moratorio_pago`, `interes_moratorio`. El significado de `capital`, `capital_pago`, `ordinario`, `ordinario_pago`, `moratorio` y `moratorio_pago` sigue sin confirmar: `capital + capital_pago = abono_capital` solo en 626 de 2,877 cuotas.

Columnas del destino que quedan vacías por no tener origen: `acreditados.clave_personalizada`, `acreditados.regimen`, `creditos.folio_solicitud`, `creditos.numero_contrato_oficial`, `creditos.fecha_contrato`, `creditos.contrato_ruta`.

---

## Créditos con 28 filas y plazo 24

Los créditos 343, 357, 358, 372 y 373 traen 3 filas más que su plazo (1 de desembolso + 27). El script los lista en `resumen.json` (`origen.filas_menos_plazo`), junto con cualquier otro crédito que no tenga plazo + 1 filas.

**Hipótesis, sin confirmar:** son 5, igual que los Sustentable, y el motor genera 3 cuotas de gracia para Sustentable ([`CreditService.php:26`](../../app/Services/CreditService.php#L26)). Si los 5 son Sustentable y sus 3 primeras filas no traen capital ni interés, son la gracia. Se confirma con el volcado real; la decisión es de Diego.

El script deja la decisión abierta con `--extra`:

| Opción | Qué hace |
|---|---|
| `rechazar` (por defecto) | Los 5 quedan fuera y en `rechazos.csv`, con la nota de si las 3 primeras filas tienen forma de gracia |
| `gracia` | Carga las 3 filas como `estado = 'Gracia'`, `numero_cuota` -3 a -1, en ceros, igual que el motor. Si alguna trae capital o interés, rechaza el crédito. |

---

## Diferencias que se importan sin corregir

Cifras de Diego sobre los datos reales. El script las mide fila por fila y las deja en `dif_cuota.csv`, `dif_saldo.csv` y `anexo_excepciones.csv`.

| Comprobación | Cumplen | No cumplen |
|---|---|---|
| `abono_capital + interes_ordinario = cuota_mensual` | 2,802 | 75 cuotas, en 75 créditos |
| El saldo baja exactamente en `abono_capital` | 2,784 | 93 |
| Suma de `abono_capital` = monto otorgado | 146 | 0 |
| Filas = plazo + 1 | 141 | 5 (sección anterior) |

`dif_cuota.csv` marca con `ultima = 1` las diferencias que caen en la última cuota (ajuste de redondeo del sistema viejo) y `resumen.json` trae cuántas son, la suma y la diferencia máxima. Si casi todas caen en la última cuota y son de centavos, es redondeo; si caen a mitad de la tabla o pasan de centavos, hay que verlas una por una.

En las 75 cuotas donde capital + interés no da la cuota, el sistema nuevo va a cobrar capital + interés, no la `cuota_mensual` del sistema viejo, porque `pago_restante` sale de esa suma. `cuota_fija` conserva el valor viejo, así que en esas cuotas el monto que se muestra y el que se cobra no coinciden.

Las 93 cuotas donde el saldo no baja en el capital van a salir en la consulta 6 de [`A5_integridad.sql`](sql/A5_integridad.sql), que reconstruye el saldo desde el monto.

---

## Procedimiento

Se corre con el código de `develop` (o la rama que vaya a producción) y MariaDB local. Nada contra producción.

| Paso | Comando o acción |
|---|---|
| 1. Base del volcado | `CREATE DATABASE crea_viejo;` y cargar el volcado: `mysql -uroot crea_viejo < volcado.sql` |
| 2. Base destino desechable | `CREATE DATABASE crea_ensayo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` |
| 3. Migraciones | `DB_DATABASE=crea_ensayo php artisan migrate --force` |
| 4. Modalidades | `DB_DATABASE=crea_ensayo php artisan db:seed --class=ModalidadCreaSeeder --force`. En producción ya existen; el script se detiene si falta alguna. |
| 5. Ajustar el mapa | Revisar las columnas `VERIFICAR` de [`importar_crea.php`](../../tools/migracion/importar_crea.php) contra `DESCRIBE crea_viejo.cliente` |
| 6. Respaldo del destino | `mysqldump -uroot crea_ensayo > antes.sql` |
| 7. Simulación | El comando del paso 8 con `--simular`. Hace todo y revierte cada transacción. Consume los autoincrementales del destino (los ids reales empiezan después), así que en producción se simula sobre la copia, no sobre la base real. |
| 8. Importación | `php tools/migracion/importar_crea.php --origen=crea_viejo --destino=crea_ensayo --confirmar-destino=crea_ensayo --salida=<carpeta fuera del repo> --extra=rechazar` |
| 9. Segunda corrida | El mismo comando. Debe reportar 0 créditos nuevos y todos en `ya_importados`. |
| 10. Verificación | Ver la sección siguiente |
| 11. Limpieza | Borrar `crea_viejo`, el archivo del volcado y la carpeta de salida (`DROP DATABASE crea_viejo;`). Traen datos personales. |

El script se niega a correr si `--confirmar-destino` no repite el nombre del destino, si el host no es local, si origen y destino son la misma base o si `--salida` cae dentro del repositorio. También se detiene si encuentra pagos que liguen un crédito CREA con cuotas de uno de Microyuc: Diego pidió que se le avise antes de decidir.

Cada crédito se escribe en una transacción (acreditado, crédito y cuotas). Si la corrida se interrumpe, no quedan créditos a medias y la siguiente corrida sigue donde se quedó.

---

## Cómo verificar

El script deja en la carpeta de salida:

| Archivo | Contenido |
|---|---|
| `resumen.json` | Conteos de origen, descartados (Microyuc y pagos), importados, rechazados, diferencias, verificación y tiempo |
| `rechazos.csv` | `cliente_id` y motivo de cada crédito que no entró |
| `insertados.csv` | `cliente_id` viejo → `credito_id` y `acreditado_id` nuevos |
| `anexo_excepciones.csv` | Créditos que entraron con diferencias que ya traían del sistema viejo |
| `dif_cuota.csv`, `dif_saldo.csv` | Detalle por cuota |
| `revertir.sql` | `DELETE` de lo insertado en esa corrida |

Ninguno lleva nombre, CURP, RFC, correo ni domicilio.

El anexo también marca los créditos de una modalidad sin interés (Artesanal, 0 %) cuya tabla trae interés.

`resumen.json` trae la verificación sobre lo importado: tabla completa (cuotas = plazo), capital de la tabla = monto otorgado, sin fechas ni montos vacíos y sin avance de pago. Las cuatro cifras deben ser iguales al número de créditos importados.

Después se corren las consultas existentes sobre `crea_ensayo`:

| Archivo | Qué debe salir |
|---|---|
| [`A5_integridad.sql`](sql/A5_integridad.sql) | Consultas 1 a 4: capital, plazo y gracia correctos. Consultas 5 y 6: solo los créditos del anexo con saldo que no baja. Consulta 7: créditos con más de un valor de `cuota_fija`, entre ellos los de `dif_cuota.csv` cuando la diferencia no cae en la última cuota. |
| [`A2_conciliacion.sql`](sql/A2_conciliacion.sql) | Cero filas y cero en el resumen (consulta 7). Sin pagos importados, cualquier fila sería avance de pago que se coló. |

La conciliación contra pagos del ticket original ya no aplica: sin pagos importados, pagos y cuotas no pueden cuadrar. La clasificación por causa queda así: lo que ya venía del sistema viejo va en `anexo_excepciones.csv`; lo que el script no pudo cargar, en `rechazos.csv`. No hay condonaciones que clasificar porque no se migran pagos.

---

## Cómo volver atrás

| Caso | Acción |
|---|---|
| Ensayo | `DROP DATABASE crea_ensayo;` |
| Producción, sistema vacío antes de migrar | Restaurar el respaldo del paso 6 |
| Producción, con capturas posteriores | `revertir.sql` de la corrida. Borra los créditos insertados (las cuotas caen en cascada) y los acreditados que creó, si no tienen otro crédito. Los pagos capturados a mano sobre esos créditos también caen en cascada. |

---

## Ensayo

| Base | Créditos CREA | Cuotas | Importados | Rechazados | Tiempo |
|---|---|---|---|---|---|
| Ficticia (20 CREA, 10 Microyuc, 60 pagos) | 20 (10 `CREA`, 10 `Crea`) | 405 de 425 filas | 13 con `--extra=rechazar`; 18 al repetir con `--extra=gracia` | 7, luego 2 | 0.1 s |
| Volcado real | 146 esperados | 2,877 esperadas | Pendiente | Pendiente | Pendiente |

Corridas sobre la ficticia, en este orden, contra `crea_ensayo` recién migrada con `develop` (`f094a5b`):

| Corrida | Resultado |
|---|---|
| `--simular` | 13 importados y revertidos. Destino sin cambios. |
| 1, `--extra=rechazar` | 13 importados (228 cuotas). Descartados: 10 créditos Microyuc, 196 filas de amortización, 60 pagos. Verificación 13 de 13 en las cuatro cifras. |
| 2, mismo comando | 0 insertados, 13 `ya_importados` |
| 3, `--extra=gracia` | 5 Sustentable más, con 15 cuotas de Gracia numeradas -3 a -1 y saldo igual al monto, como el motor |

Rechazos provocados a propósito: municipio vacío, fecha de vencimiento vacía y los 5 créditos con plazo + 3 cuotas (solo con `rechazar`). Diferencias provocadas: una cuota con capital + interés ≠ cuota a mitad de tabla (-0.37), otra en la última cuota (0.02) y un saldo que no baja en el capital. Las tres salieron en el anexo.

Sobre las 18 importadas, A5 dio 18 de 18 en capital = monto, plazo y gracia; la consulta 6 marcó solo el crédito con el saldo alterado y la 7 solo el de la diferencia a mitad de tabla. A2 dio cero en todas las columnas.

---

## Lo que no pude determinar

- Los nombres reales de las columnas de `cliente`, salvo `acreditado_tipo`, `tipo_credito` y `estatus`.
- Si el volcado trae una clave de contrato que deba usarse como `clave_contrato`.
- Si los 5 créditos con 28 filas son los 5 Sustentable y si sus 3 filas sobrantes son la gracia.
- En qué unidad viene `taza_moratoria`. El destino espera porcentaje anual (12.50, 17.50).
- Si `pago` tiene alguna columna que ligue un pago a una cuota. El script revisa `amortizacion_id`; si no existe, no puede detectar cruces CREA ↔ Microyuc por esa vía.
- Cuántos de los 146 son la misma persona con más de un crédito.

---

## Recomendación

Correr el ensayo con el volcado real con `--extra=rechazar` y decidir los 5 créditos con el resultado en mano. La decisión sobre las 75 cuotas con cuota distinta a capital + interés es de dirección, no de la migración.
