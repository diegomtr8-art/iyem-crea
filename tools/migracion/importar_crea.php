<?php
/**
 * Importación de créditos CREA del sistema viejo (crea-iyem.com) a CREA.
 * Autor: Jesús Alejandro Mena Márquez
 *
 * Alcance (acordado con Diego):
 *   * Solo créditos de la tabla `cliente` con acreditado_tipo CREA/Crea.
 *     Microyuc se descarta con sus cuotas.
 *   * Créditos y cuotas. Los pagos NO se migran: se capturan a mano.
 *   * Las cuotas entran como NO pagadas (estado Pendiente, *_pagado = 0).
 *   * No corrige cifras históricas: lo que no cuadra se importa tal cual
 *     y se lista en el anexo; lo que no se puede cargar se rechaza.
 *
 * Idempotente: un crédito cuya clave_contrato ya existe en el destino se
 * salta. Cada crédito (acreditado + crédito + cuotas) va en su propia
 * transacción, así que una corrida interrumpida no deja créditos a medias.
 *
 * Uso (solo contra una copia desechable):
 *   php tools/migracion/importar_crea.php --origen=crea_viejo \
 *       --destino=crea_ensayo --confirmar-destino=crea_ensayo \
 *       --salida=C:/ruta/fuera/del/repo [--extra=rechazar|gracia] [--simular]
 *
 *   --origen     base donde se cargó el volcado del sistema viejo
 *   --destino    base CREA con las migraciones de develop y modalidades
 *   --salida     carpeta para los reportes. Fuera del repositorio.
 *   --extra      qué hacer con los créditos que traen 3 filas más que su
 *                plazo (343, 357, 358, 372, 373). `rechazar` (por defecto)
 *                los deja fuera hasta que Diego decida; `gracia` carga las
 *                3 filas sin capital ni interés como cuotas de Gracia.
 *   --simular    hace todo y al final revierte cada transacción.
 *   --host --puerto --usuario --clave   conexión (127.0.0.1 / root por defecto)
 *
 * Los reportes no llevan nombres, CURP, RFC, correo ni domicilio: solo ids
 * del sistema viejo, ids nuevos y montos.
 */

// ───────────────────────── Mapa de columnas ─────────────────────────
// amortizacion: confirmado por Diego. cliente: los nombres marcados con
// "VERIFICAR" se ajustan contra el volcado antes del primer ensayo; el
// script se detiene si alguno no existe.
const CLIENTE = [
    'id'             => 'id',
    'tipo'           => 'acreditado_tipo',
    'modalidad'      => 'tipo_credito',
    'estatus'        => 'estatus',
    'nombre'         => 'nombre',          // VERIFICAR
    'curp'           => 'curp',            // VERIFICAR
    'rfc'            => 'rfc',             // VERIFICAR
    'correo'         => 'correo',          // VERIFICAR
    'domicilio'      => 'domicilio',       // VERIFICAR
    'municipio'      => 'municipio',       // VERIFICAR
    'sexo'           => 'sexo',            // VERIFICAR
    'monto'          => 'monto',           // VERIFICAR
    'plazo'          => 'plazo',           // VERIFICAR
    'fecha_entrega'  => 'fecha_entrega',   // VERIFICAR
    'clave_contrato' => null,              // VERIFICAR; null = se genera CREA-V-{id}
];
const AMORT = [
    'tabla'    => 'amortizacion',
    'credito'  => 'cliente_id',
    'mes'      => 'mes',
    'fecha'    => 'fecha_pago',
    'capital'  => 'abono_capital',
    'interes'  => 'interes_ordinario',
    'cuota'    => 'cuota_mensual',
    'saldo'    => 'saldo',
    'tasa_mor' => 'taza_moratoria',
];
const TABLA_CLIENTE = 'cliente';
const TABLA_PAGO    = 'pago';
const MODALIDADES   = [
    'artesanal'     => 'Artesanal',
    'emprendedor'   => 'Emprendedores',
    'emprendedores' => 'Emprendedores',
    'sustentable'   => 'Sustentable',
];
const TOLERANCIA = 0.01;

// ───────────────────────── Argumentos y conexión ─────────────────────────
$o = getopt('', ['origen:', 'destino:', 'confirmar-destino:', 'salida:', 'extra:', 'simular',
                 'host:', 'puerto:', 'usuario:', 'clave:']);
foreach (['origen', 'destino', 'salida'] as $req) {
    if (empty($o[$req])) fallar("Falta --$req. Ver el encabezado del script.");
}
$origen  = $o['origen'];
$destino = $o['destino'];
$extra   = $o['extra'] ?? 'rechazar';
$simular = isset($o['simular']);
$host    = $o['host'] ?? '127.0.0.1';

if (($o['confirmar-destino'] ?? '') !== $destino) {
    fallar("Para escribir en '$destino' repite su nombre en --confirmar-destino.");
}
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fallar("El ensayo solo corre contra una base local. Host recibido: $host");
}
if (!in_array($extra, ['rechazar', 'gracia'], true)) fallar("--extra debe ser rechazar o gracia.");
if ($origen === $destino) fallar('Origen y destino no pueden ser la misma base.');

$salida = rtrim($o['salida'], '/\\');
if (!is_dir($salida) && !mkdir($salida, 0700, true)) fallar("No pude crear $salida");
$repo = realpath(__DIR__ . '/../..');
if (str_starts_with(realpath($salida), $repo)) {
    fallar('--salida no puede quedar dentro del repositorio.');
}

$dsn = "mysql:host=$host;port=" . ($o['puerto'] ?? 3306) . ';charset=utf8mb4';
$db  = new PDO($dsn, $o['usuario'] ?? 'root', $o['clave'] ?? '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$t0 = microtime(true);
$O  = "`$origen`";
$D  = "`$destino`";

// ───────────────────────── 1. Esquemas ─────────────────────────
$faltan = [];
$colsCliente = columnas($db, $origen, TABLA_CLIENTE);
foreach (CLIENTE as $k => $c) if ($c !== null && !in_array($c, $colsCliente, true)) $faltan[] = TABLA_CLIENTE . ".$c";
$colsAmort = columnas($db, $origen, AMORT['tabla']);
foreach (AMORT as $k => $c) if ($k !== 'tabla' && !in_array($c, $colsAmort, true)) $faltan[] = AMORT['tabla'] . ".$c";
foreach (['acreditados', 'creditos', 'amortizaciones', 'modalidad_creas'] as $t) {
    if (!columnas($db, $destino, $t)) $faltan[] = "$destino.$t (corre las migraciones de develop)";
}
if ($faltan) fallar("Columnas o tablas que no existen:\n  - " . implode("\n  - ", $faltan));

$modalidadId = [];
foreach ($db->query("SELECT id, nombre, tasa_interes, tasa_moratoria FROM $D.modalidad_creas") as $m) {
    $modalidadId[strtolower(trim($m['nombre']))] = $m;
}
foreach (array_unique(MODALIDADES) as $nombre) {
    if (!isset($modalidadId[strtolower($nombre)])) fallar("Falta la modalidad '$nombre' en $destino.modalidad_creas.");
}

// ───────────────────────── 2. Conteos de origen y filtro ─────────────────────────
$cT = CLIENTE['tipo'];
$aT = AMORT['tabla']; $aC = AMORT['credito']; $aM = AMORT['mes'];
$esCrea = "UPPER(TRIM(c.`$cT`)) = 'CREA'";

$r = [];
$r['origen']['tipos'] = $db->query("SELECT c.`$cT` AS tipo, COUNT(*) AS n FROM $O.`" . TABLA_CLIENTE . "` c GROUP BY BINARY c.`$cT` ORDER BY n DESC")->fetchAll();
$r['origen']['creditos']        = (int) $db->query("SELECT COUNT(*) FROM $O.`" . TABLA_CLIENTE . "`")->fetchColumn();
$r['origen']['creditos_crea']   = (int) $db->query("SELECT COUNT(*) FROM $O.`" . TABLA_CLIENTE . "` c WHERE $esCrea")->fetchColumn();
$r['origen']['filas_amort']     = (int) $db->query("SELECT COUNT(*) FROM $O.`$aT`")->fetchColumn();
$r['origen']['filas_amort_crea'] = (int) $db->query("SELECT COUNT(*) FROM $O.`$aT` a JOIN $O.`" . TABLA_CLIENTE . "` c ON c.`" . CLIENTE['id'] . "` = a.`$aC` WHERE $esCrea")->fetchColumn();
$r['origen']['cuotas_crea']     = (int) $db->query("SELECT COUNT(*) FROM $O.`$aT` a JOIN $O.`" . TABLA_CLIENTE . "` c ON c.`" . CLIENTE['id'] . "` = a.`$aC` WHERE $esCrea AND a.`$aM` > 0")->fetchColumn();
$r['origen']['amort_huerfanas'] = (int) $db->query("SELECT COUNT(*) FROM $O.`$aT` a LEFT JOIN $O.`" . TABLA_CLIENTE . "` c ON c.`" . CLIENTE['id'] . "` = a.`$aC` WHERE c.`" . CLIENTE['id'] . "` IS NULL")->fetchColumn();
// Forma de la tabla: filas por crédito menos plazo (Diego espera 141 con +1 y 5 con +4).
$cP = CLIENTE['plazo'];
$r['origen']['filas_menos_plazo'] = $db->query(
    "SELECT t.dif, COUNT(*) AS creditos, GROUP_CONCAT(IF(t.dif <> 1, t.id, NULL) ORDER BY t.id) AS cliente_ids
       FROM (SELECT c.`" . CLIENTE['id'] . "` AS id, COUNT(a.`$aC`) - c.`$cP` AS dif
               FROM $O.`" . TABLA_CLIENTE . "` c LEFT JOIN $O.`$aT` a ON a.`$aC` = c.`" . CLIENTE['id'] . "`
              WHERE $esCrea GROUP BY c.`" . CLIENTE['id'] . "`, c.`$cP`) t
      GROUP BY t.dif ORDER BY t.dif")->fetchAll();
$colsPago = columnas($db, $origen, TABLA_PAGO);
$r['origen']['pagos'] = $colsPago ? (int) $db->query("SELECT COUNT(*) FROM $O.`" . TABLA_PAGO . "`")->fetchColumn() : null;

$r['descartados'] = [
    'creditos_no_crea'    => $r['origen']['creditos'] - $r['origen']['creditos_crea'],
    'filas_amort_no_crea' => $r['origen']['filas_amort'] - $r['origen']['filas_amort_crea'],
    'pagos_todos'         => $r['origen']['pagos'],
];

// Cruces CREA ↔ Microyuc: Diego pidió que se le avise antes de decidir.
$cruces = [];
if (in_array('amortizacion_id', $colsPago, true) && in_array('cliente_id', $colsPago, true)) {
    $cruces = $db->query(
        "SELECT p.id AS pago_id, p.cliente_id AS credito_pago, a.`$aC` AS credito_cuota
           FROM $O.`" . TABLA_PAGO . "` p
           JOIN $O.`$aT` a ON a.id = p.amortizacion_id
           JOIN $O.`" . TABLA_CLIENTE . "` c1 ON c1.`" . CLIENTE['id'] . "` = p.cliente_id
           JOIN $O.`" . TABLA_CLIENTE . "` c2 ON c2.`" . CLIENTE['id'] . "` = a.`$aC`
          WHERE (UPPER(TRIM(c1.`$cT`)) = 'CREA') <> (UPPER(TRIM(c2.`$cT`)) = 'CREA')")->fetchAll();
}
$r['cruces_crea_microyuc'] = count($cruces);
if (CLIENTE['curp'] !== null) {
    $cu = CLIENTE['curp'];
    $r['personas_en_crea_y_microyuc'] = (int) $db->query(
        "SELECT COUNT(DISTINCT UPPER(TRIM(c.`$cu`))) FROM $O.`" . TABLA_CLIENTE . "` c
          WHERE $esCrea AND TRIM(COALESCE(c.`$cu`, '')) <> ''
            AND UPPER(TRIM(c.`$cu`)) IN (SELECT UPPER(TRIM(m.`$cu`)) FROM $O.`" . TABLA_CLIENTE . "` m
                                          WHERE UPPER(TRIM(m.`$cT`)) <> 'CREA')")->fetchColumn();
}
if ($cruces) {
    escribirCsv("$salida/cruces_crea_microyuc.csv", $cruces);
    fallar(count($cruces) . " pagos cruzan un crédito CREA con uno de Microyuc. Ver cruces_crea_microyuc.csv y avisar a Diego antes de seguir.");
}

// ───────────────────────── 3. Importación por crédito ─────────────────────────
$sel = [];
foreach (CLIENTE as $k => $c) if ($c !== null) $sel[] = "c.`$c` AS `$k`";
$creditos = $db->query("SELECT " . implode(', ', $sel) . " FROM $O.`" . TABLA_CLIENTE . "` c WHERE $esCrea ORDER BY c.`" . CLIENTE['id'] . "`")->fetchAll();

$stCuotas = $db->prepare(
    "SELECT `$aM` AS mes, `" . AMORT['fecha'] . "` AS fecha, `" . AMORT['capital'] . "` AS capital,
            `" . AMORT['interes'] . "` AS interes, `" . AMORT['cuota'] . "` AS cuota,
            `" . AMORT['saldo'] . "` AS saldo, `" . AMORT['tasa_mor'] . "` AS tasa_mor
       FROM $O.`$aT` WHERE `$aC` = ? ORDER BY `$aM`");
$stExiste   = $db->prepare("SELECT id, monto_otorgado FROM $D.creditos WHERE clave_contrato = ?");
$stPorCurp  = $db->prepare("SELECT id FROM $D.acreditados WHERE curp = ?");
$stPorRfc   = $db->prepare("SELECT id, curp FROM $D.acreditados WHERE rfc = ?");
$stAcred    = $db->prepare("INSERT INTO $D.acreditados (nombre_completo, rfc, curp, municipio, correo, sexo, direccion_fiscal, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
$stCredito  = $db->prepare("INSERT INTO $D.creditos (acreditado_id, modalidad_id, clave_contrato, monto_otorgado, plazo_meses, fecha_entrega, tasa_interes_ordinario, tasa_interes_moratorio, estatus, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'Activo',NOW(),NOW())");
$stCuota    = $db->prepare("INSERT INTO $D.amortizaciones (credito_id, numero_cuota, fecha_vencimiento, saldo_insoluto, capital_esperado, interes_ordinario_esperado, cuota_fija, capital_pagado, interes_ordinario_pagado, interes_moratorio_pagado, interes_moratorio_generado, comision_pagada, moratorio_acumulado, pago_restante, estado, fecha_ultimo_pago, observaciones, created_at, updated_at) VALUES (?,?,?,?,?,?,?,0,0,0,0,0,0,?,?,NULL,?,NOW(),NOW())");

$rechazos = []; $insertados = []; $difCuota = []; $difSaldo = []; $anexo = [];
$cont = ['creditos' => 0, 'cuotas' => 0, 'gracia' => 0, 'acreditados_nuevos' => 0, 'acreditados_reusados' => 0, 'ya_importados' => 0];

foreach ($creditos as $cr) {
    $id    = $cr['id'];
    $clave = CLIENTE['clave_contrato'] !== null ? trim((string) $cr['clave_contrato']) : "CREA-V-$id";
    $rech  = function (string $motivo) use (&$rechazos, $id) { $rechazos[] = ['tabla' => TABLA_CLIENTE, 'cliente_id' => $id, 'motivo' => $motivo]; };

    // Idempotencia
    $stExiste->execute([$clave]);
    if ($ya = $stExiste->fetch()) {
        if (abs($ya['monto_otorgado'] - (float) $cr['monto']) > TOLERANCIA) $rech("clave_contrato $clave ya existe con otro monto");
        else $cont['ya_importados']++;
        continue;
    }

    // Crédito
    $mod = MODALIDADES[strtolower(trim((string) $cr['modalidad']))] ?? null;
    if (!$mod)                                             { $rech("modalidad no reconocida: '{$cr['modalidad']}'"); continue; }
    if (strtolower(trim((string) $cr['estatus'])) !== 'activo') { $rech("estatus no esperado: '{$cr['estatus']}'"); continue; }
    $monto = num($cr['monto']); $plazo = (int) $cr['plazo']; $entrega = fecha($cr['fecha_entrega']);
    if ($monto === null || $monto <= 0) { $rech('monto vacío o no positivo'); continue; }
    if ($plazo <= 0)                    { $rech('plazo vacío'); continue; }
    if ($entrega === null)              { $rech('fecha_entrega vacía o inválida'); continue; }
    if (trim((string) $cr['nombre']) === '')    { $rech('nombre vacío'); continue; }
    if (trim((string) $cr['municipio']) === '') { $rech('municipio vacío (obligatorio en acreditados)'); continue; }

    // Cuotas
    $stCuotas->execute([$id]);
    $filas = $stCuotas->fetchAll();
    $cero  = array_values(array_filter($filas, fn($f) => (int) $f['mes'] === 0));
    $cuotas = array_values(array_filter($filas, fn($f) => (int) $f['mes'] > 0));
    if (count($cero) !== 1) { $rech('se esperaba 1 fila mes = 0 (desembolso), hay ' . count($cero)); continue; }
    if (count(array_unique(array_column($cuotas, 'mes'))) !== count($cuotas)) { $rech('números de mes repetidos'); continue; }

    $gracia = [];
    if (count($cuotas) === $plazo + 3) {
        $primeras = array_slice($cuotas, 0, 3);
        $sinMonto = array_reduce($primeras, fn($ok, $f) => $ok && abs(num($f['capital']) ?? 1) < TOLERANCIA && abs(num($f['interes']) ?? 1) < TOLERANCIA, true);
        if ($extra !== 'gracia') { $rech("tiene plazo + 3 cuotas" . ($sinMonto ? ' (las 3 primeras sin capital ni interés)' : '') . '; pendiente de decisión de Diego'); continue; }
        if (!$sinMonto)          { $rech('tiene plazo + 3 cuotas y las 3 primeras traen capital o interés: no son gracia'); continue; }
        $gracia = $primeras;
        $cuotas = array_slice($cuotas, 3);
    }
    if (count($cuotas) !== $plazo) { $rech('tiene ' . count($cuotas) . " cuotas y plazo $plazo"); continue; }

    $vacio = null;
    foreach (array_merge($gracia, $cuotas) as $f) {
        foreach (['fecha', 'capital', 'interes', 'cuota', 'saldo'] as $campo) {
            $v = $campo === 'fecha' ? fecha($f['fecha']) : num($f[$campo]);
            if ($v === null || ($campo !== 'fecha' && $v < 0)) { $vacio = "mes {$f['mes']}: $campo vacío, inválido o negativo"; break 2; }
        }
    }
    if ($vacio) { $rech($vacio); continue; }

    $tasasMor = array_unique(array_map(fn($f) => (string) num($f['tasa_mor']), $cuotas));
    $tasaMor  = (float) max($tasasMor);
    $m        = $modalidadId[strtolower($mod)];

    // Diferencias que se reportan y no se corrigen
    $sumaCapital = round(array_sum(array_map(fn($f) => num($f['capital']), $cuotas)), 2);
    $prev = num($cero[0]['saldo']);
    $obs  = [];
    $nCuota = $sCuota = $nSaldo = 0;
    foreach ($cuotas as $f) {
        $d = round(num($f['capital']) + num($f['interes']) - num($f['cuota']), 2);
        if (abs($d) > TOLERANCIA) {
            $difCuota[] = ['cliente_id' => $id, 'mes' => $f['mes'], 'ultima' => (int) ($f === end($cuotas)), 'capital' => $f['capital'], 'interes' => $f['interes'], 'cuota_mensual' => $f['cuota'], 'diferencia' => $d];
            $nCuota++; $sCuota += $d;
        }
        $ds = round($prev - num($f['capital']) - num($f['saldo']), 2);
        if (abs($ds) > TOLERANCIA) {
            $difSaldo[] = ['cliente_id' => $id, 'mes' => $f['mes'], 'saldo_anterior' => $prev, 'capital' => $f['capital'], 'saldo' => $f['saldo'], 'diferencia' => $ds];
            $nSaldo++;
        }
        $prev = num($f['saldo']);
    }
    if ($nCuota) $obs[] = "$nCuota cuota(s) con capital + interés ≠ cuota_mensual (suma " . round($sCuota, 2) . ')';
    if ($nSaldo) $obs[] = "$nSaldo cuota(s) donde el saldo no baja en el capital";
    if (abs($sumaCapital - $monto) > TOLERANCIA)              $obs[] = "capital en tabla $sumaCapital ≠ monto $monto";
    if (abs(num($cero[0]['saldo']) - $monto) > TOLERANCIA)    $obs[] = "saldo inicial (mes 0) {$cero[0]['saldo']} ≠ monto $monto";
    if (count($tasasMor) > 1)                                 $obs[] = 'tasa moratoria varía entre cuotas (' . implode(', ', $tasasMor) . "); se toma $tasaMor";
    if (abs($tasaMor - (float) $m['tasa_moratoria']) > TOLERANCIA) $obs[] = "tasa moratoria $tasaMor ≠ la de la modalidad {$m['tasa_moratoria']}";
    $sumaInteres = round(array_sum(array_map(fn($f) => num($f['interes']), $cuotas)), 2);
    if ((float) $m['tasa_interes'] == 0.0 && $sumaInteres > TOLERANCIA) $obs[] = "modalidad $mod sin interés y la tabla trae $sumaInteres de interés";
    $c0 = fecha($cero[0]['fecha']);
    if ($c0 !== null && $c0 !== $entrega)                     $obs[] = "fecha del mes 0 ($c0) ≠ fecha_entrega ($entrega)";

    // Escritura
    try {
        $db->beginTransaction();

        $curp = vacioANull(strtoupper(trim((string) $cr['curp'])));
        $rfc  = vacioANull(strtoupper(trim((string) $cr['rfc'])));
        $acredId = null;
        if ($curp) { $stPorCurp->execute([$curp]); $acredId = $stPorCurp->fetchColumn() ?: null; }
        if (!$acredId && $rfc) {
            $stPorRfc->execute([$rfc]);
            $porRfc = $stPorRfc->fetch();
            if ($porRfc && $curp && $porRfc['curp'] && $porRfc['curp'] !== $curp) {
                throw new RuntimeException('el RFC ya está registrado con otra CURP');
            }
            $acredId = $porRfc['id'] ?? null;
        }
        $nuevo = !$acredId;
        if ($nuevo) {
            $stAcred->execute([trim($cr['nombre']), $rfc, $curp, trim($cr['municipio']),
                vacioANull(trim((string) $cr['correo'])), sexo($cr['sexo']), vacioANull(trim((string) $cr['domicilio']))]);
            $acredId = (int) $db->lastInsertId();
        }

        $stCredito->execute([$acredId, $m['id'], $clave, $monto, $plazo, $entrega, $m['tasa_interes'], $tasaMor]);
        $creditoId = (int) $db->lastInsertId();

        $n = -count($gracia);
        foreach ($gracia as $f) {
            $stCuota->execute([$creditoId, $n++, fecha($f['fecha']), $monto, 0, 0, 0, 0, 'Gracia', 'Período de gracia — sin pago requerido']);
        }
        $n = 1;
        $saldoAntes = $gracia ? num(end($gracia)['saldo']) : num($cero[0]['saldo']);
        foreach ($cuotas as $f) {
            $cap = num($f['capital']); $int = num($f['interes']);
            // saldo_insoluto del motor = saldo ANTES de la cuota; el viejo guarda el de después.
            // pago_restante = capital + interés, que es lo que el motor cobra al registrar pagos.
            $stCuota->execute([$creditoId, $n++, fecha($f['fecha']), $saldoAntes, $cap, $int, num($f['cuota']), round($cap + $int, 2), 'Pendiente', null]);
            $saldoAntes = num($f['saldo']);
        }

        $simular ? $db->rollBack() : $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $rech('error al insertar: ' . preg_replace('/\s+/', ' ', $e->getMessage()));
        continue;
    }

    $cont['creditos']++;
    $cont['cuotas'] += count($cuotas);
    $cont['gracia'] += count($gracia);
    $cont[$nuevo ? 'acreditados_nuevos' : 'acreditados_reusados']++;
    $insertados[] = ['cliente_id' => $id, 'credito_id' => $creditoId, 'acreditado_id' => $acredId, 'acreditado_nuevo' => (int) $nuevo, 'clave_contrato' => $clave, 'modalidad' => $mod, 'monto' => $monto, 'plazo' => $plazo, 'cuotas' => count($cuotas), 'gracia' => count($gracia)];
    if ($obs) $anexo[] = ['cliente_id' => $id, 'credito_id' => $creditoId, 'observaciones' => implode(' | ', $obs)];
}

// ───────────────────────── 4. Verificación sobre lo importado ─────────────────────────
$ids = array_column($insertados, 'credito_id');
$verif = [];
if ($ids && !$simular) {
    $in = implode(',', array_map('intval', $ids));
    $verif = $db->query(
        "SELECT
            COUNT(*) AS creditos,
            SUM(t.cuotas = c.plazo_meses)                         AS tabla_completa,
            SUM(ABS(t.capital - c.monto_otorgado) <= 0.01)        AS capital_igual_monto,
            SUM(t.vacios = 0)                                     AS sin_vacios,
            SUM(t.pagado = 0 AND t.no_pendientes = 0)             AS sin_avance_de_pago
           FROM $D.creditos c
           JOIN (SELECT credito_id,
                        SUM(estado <> 'Gracia') AS cuotas,
                        SUM(capital_esperado) AS capital,
                        SUM(fecha_vencimiento IS NULL OR capital_esperado IS NULL OR interes_ordinario_esperado IS NULL OR cuota_fija IS NULL OR saldo_insoluto IS NULL) AS vacios,
                        SUM(capital_pagado + interes_ordinario_pagado + interes_moratorio_pagado + comision_pagada) AS pagado,
                        SUM(estado NOT IN ('Pendiente','Gracia') OR fecha_ultimo_pago IS NOT NULL) AS no_pendientes
                   FROM $D.amortizaciones WHERE credito_id IN ($in) GROUP BY credito_id) t ON t.credito_id = c.id
          WHERE c.id IN ($in)")->fetch();
}

// ───────────────────────── 5. Reportes ─────────────────────────
$r['extra']        = $extra;
$r['simulacion']   = $simular;
$r['importados']   = $cont;
$r['rechazados']   = count($rechazos);
$r['diferencias']  = [
    'cuotas_capital_mas_interes_distinto_de_cuota' => count($difCuota),
    'creditos_con_esa_diferencia'                  => count(array_unique(array_column($difCuota, 'cliente_id'))),
    'de_esas_en_la_ultima_cuota'                   => array_sum(array_column($difCuota, 'ultima')),
    'suma_diferencias'                             => round(array_sum(array_column($difCuota, 'diferencia')), 2),
    'diferencia_max_absoluta'                      => $difCuota ? max(array_map(fn($x) => abs($x['diferencia']), $difCuota)) : 0,
    'cuotas_saldo_no_baja_en_capital'              => count($difSaldo),
    'creditos_en_anexo'                            => count($anexo),
];
$r['verificacion'] = $verif;
$r['segundos']     = round(microtime(true) - $t0, 1);

escribirCsv("$salida/rechazos.csv", $rechazos);
escribirCsv("$salida/insertados.csv", $insertados);
escribirCsv("$salida/dif_cuota.csv", $difCuota);
escribirCsv("$salida/dif_saldo.csv", $difSaldo);
escribirCsv("$salida/anexo_excepciones.csv", $anexo);
file_put_contents("$salida/resumen.json", json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($ids && !$simular) {
    $nuevos = array_column(array_filter($insertados, fn($x) => $x['acreditado_nuevo']), 'acreditado_id');
    file_put_contents("$salida/revertir.sql",
        "-- Revierte la corrida del " . date('Y-m-d H:i') . " en $destino. Las cuotas se borran en cascada.\n" .
        "DELETE FROM $D.creditos WHERE id IN (" . implode(',', $ids) . ");\n" .
        ($nuevos ? "DELETE FROM $D.acreditados WHERE id IN (" . implode(',', $nuevos) . ")\n  AND NOT EXISTS (SELECT 1 FROM $D.creditos c WHERE c.acreditado_id = acreditados.id);\n" : ''));
}

echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
echo "Reportes en $salida\n";

// ───────────────────────── Utilidades ─────────────────────────
function fallar(string $m): never { fwrite(STDERR, "ERROR: $m\n"); exit(1); }

function columnas(PDO $db, string $base, string $tabla): array
{
    $st = $db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $st->execute([$base, $tabla]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

function num($v): ?float
{
    if ($v === null) return null;
    $v = str_replace([',', '$', ' '], '', (string) $v);
    return is_numeric($v) ? round((float) $v, 2) : null;
}

function fecha($v): ?string
{
    if ($v === null || trim((string) $v) === '' || str_starts_with((string) $v, '0000')) return null;
    $t = strtotime((string) $v);
    return $t === false ? null : date('Y-m-d', $t);
}

function vacioANull(?string $v): ?string { return ($v === null || $v === '') ? null : $v; }

function sexo($v): ?string
{
    // Una "M" sola puede ser Mujer o Masculino: sin la palabra completa queda en NULL.
    return match (strtolower(trim((string) $v))) {
        'h', 'hombre', 'masculino'          => 'H',
        'mujer', 'femenino', 'f'            => 'M',
        default                             => null,
    };
}

function escribirCsv(string $ruta, array $filas): void
{
    $f = fopen($ruta, 'w');
    fwrite($f, "\xEF\xBB\xBF");
    if ($filas) {
        fputcsv($f, array_keys($filas[0]));
        foreach ($filas as $fila) fputcsv($f, $fila);
    }
    fclose($f);
}
