<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class RespaldoBase extends Command
{
    // Después de cada respaldo exitoso se borran los que tengan más días que estos
    public const DIAS_RETENCION = 30;

    // crea-respaldo-2026-10-05_030000.sql.gz (fecha y hora de Mérida)
    private const PATRON_NOMBRE = '/^crea-respaldo-(\d{4}-\d{2}-\d{2})_\d{6}\.sql\.gz$/';

    protected $signature   = 'crea:respaldo-base {--conexion= : Conexión de config/database.php; si no se indica, la principal}';
    protected $description = 'Genera un volcado comprimido de la base de datos y borra los de más de 30 días';

    public function handle(): int
    {
        $directorio = rtrim(config('respaldo.directorio'), '/\\');
        $base       = $directorio . DIRECTORY_SEPARATOR . 'crea-respaldo-' . now('America/Merida')->format('Y-m-d_His');

        $volcado      = "{$base}.sql";
        $credenciales = "{$base}.cnf";
        $parcial      = "{$base}.sql.gz.part";
        $respaldo     = "{$base}.sql.gz";

        try {
            $conexion = DB::connection($this->option('conexion'))->getConfig();

            $this->prepararDirectorio($directorio);
            $this->volcar($conexion, $volcado, $credenciales);
            $this->comprimir($volcado, $parcial);

            if (! rename($parcial, $respaldo)) {
                throw new RuntimeException("No se pudo guardar {$respaldo}.");
            }

            $borrados = $this->borrarAntiguos($directorio);
        } catch (Throwable $e) {
            Log::error('Falló el respaldo de la base de datos (crea:respaldo-base)', ['error' => $e->getMessage()]);
            $this->error("Falló el respaldo: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            // El volcado sin comprimir y la contraseña nunca se quedan en disco, salga bien o mal
            File::delete([$volcado, $credenciales, $parcial]);
        }

        $this->info(sprintf(
            'Respaldo generado: %s (%s KB). Respaldos de más de %d días borrados: %d.',
            $respaldo, number_format(filesize($respaldo) / 1024, 1), self::DIAS_RETENCION, $borrados
        ));

        return self::SUCCESS;
    }

    private function prepararDirectorio(string $directorio): void
    {
        File::ensureDirectoryExists($directorio, 0700);

        // Por si la carpeta queda dentro de la raíz web: que el servidor no entregue los archivos
        if (! File::exists("{$directorio}/.htaccess")) {
            File::put("{$directorio}/.htaccess", "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
        }
    }

    private function volcar(array $conexion, string $volcado, string $credenciales): void
    {
        if (! in_array($conexion['driver'], ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Solo se respaldan bases MySQL/MariaDB; la conexión actual es {$conexion['driver']}.");
        }

        // La contraseña va en un archivo temporal y no en la línea de comandos, donde la verían otros procesos.
        // Se escapan comillas y diagonales para que mysqldump lea completo un valor con ", \ o #.
        $valor    = fn ($texto) => '"' . addcslashes((string) $texto, '\\"') . '"';
        $opciones = ['[client]', 'user=' . $valor($conexion['username']), 'password=' . $valor($conexion['password'])];

        if (! empty($conexion['unix_socket'])) {
            $opciones[] = 'socket=' . $valor($conexion['unix_socket']);
        } else {
            $opciones[] = 'host=' . $valor($conexion['host']);
            $opciones[] = 'port=' . (int) $conexion['port'];
        }

        File::put($credenciales, implode("\n", $opciones) . "\n");
        File::chmod($credenciales, 0600);

        $resultado = Process::timeout(1800)->run([
            config('respaldo.mysqldump'),
            "--defaults-extra-file={$credenciales}", // tiene que ser la primera opción
            '--single-transaction',                   // copia consistente sin bloquear las tablas
            '--quick',
            '--no-tablespaces',                       // así no pide el permiso PROCESS, que un usuario de hosting no tiene
            '--default-character-set=utf8mb4',
            "--result-file={$volcado}",
            $conexion['database'],
        ]);

        if ($resultado->failed()) {
            throw new RuntimeException('mysqldump terminó con código ' . $resultado->exitCode() . ': ' . trim($resultado->errorOutput()));
        }

        // mysqldump escribe esta línea al final solo cuando terminó el volcado completo
        if (! File::exists($volcado) || ! str_contains(file_get_contents($volcado, false, null, max(0, filesize($volcado) - 200)), '-- Dump completed')) {
            throw new RuntimeException('El volcado quedó incompleto: no termina con "-- Dump completed".');
        }
    }

    private function comprimir(string $volcado, string $destino): void
    {
        $entrada = fopen($volcado, 'rb');
        $salida  = fopen("compress.zlib://{$destino}", 'wb');
        stream_copy_to_stream($entrada, $salida);
        fclose($entrada);
        fclose($salida);

        // Solo se da por bueno si al descomprimirlo sale exactamente el mismo volcado
        if (hash_file('sha256', "compress.zlib://{$destino}") !== hash_file('sha256', $volcado)) {
            throw new RuntimeException('El archivo comprimido no coincide con el volcado.');
        }
    }

    private function borrarAntiguos(string $directorio): int
    {
        $limite   = now('America/Merida')->startOfDay()->subDays(self::DIAS_RETENCION);
        $borrados = 0;

        foreach (File::files($directorio) as $archivo) {
            // Solo toca archivos con el nombre que pone este comando
            if (preg_match(self::PATRON_NOMBRE, $archivo->getFilename(), $fecha)
                && Carbon::parse($fecha[1], 'America/Merida')->lt($limite)) {
                File::delete($archivo->getPathname());
                $borrados++;
            }
        }

        return $borrados;
    }
}
