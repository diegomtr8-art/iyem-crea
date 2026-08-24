<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DescargarLogosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crea:descargar-logos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga los logos institucionales (IYEM, Gobierno de Yucatán) para los PDFs; genera un respaldo local si no hay conexión.';

    private const DESTINO = [
        'escudo-yucatan.png' => 'https://www.yucatan.gob.mx/img/escudo-yucatan.png',
        'logo-iyem.png'      => 'https://iyem.yucatan.gob.mx/img/logo-iyem-blanco.png',
    ];

    public function handle(): int
    {
        $dir = public_path('img/logos');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach (self::DESTINO as $archivo => $url) {
            $destino = $dir . DIRECTORY_SEPARATOR . $archivo;

            if ($this->intentarDescargar($url, $destino)) {
                $this->info("Descargado: {$archivo}");
                continue;
            }

            $this->warn("No se pudo descargar {$archivo} desde {$url}. Generando logo de respaldo...");
            $this->generarFallback($archivo, $destino);
            $this->info("Logo de respaldo creado: {$archivo}");
        }

        return self::SUCCESS;
    }

    private function intentarDescargar(string $url, string $destino): bool
    {
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful() && str_starts_with($response->header('Content-Type', ''), 'image/')) {
                file_put_contents($destino, $response->body());
                return true;
            }
        } catch (\Throwable $e) {
            // sin conexión / host inválido — se usa el fallback local
        }

        return false;
    }

    /**
     * Genera un PNG simple con GD como respaldo cuando no se puede descargar el logo oficial.
     */
    private function generarFallback(string $archivo, string $destino): void
    {
        $img = imagecreatetruecolor(200, 200);
        imagesavealpha($img, true);
        $transparente = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparente);

        $verdeIyem = imagecolorallocate($img, 0x00, 0x68, 0x47);
        $blanco    = imagecolorallocate($img, 255, 255, 255);

        if (str_contains($archivo, 'escudo')) {
            // Círculo verde con "GY" (Gobierno de Yucatán)
            imagefilledellipse($img, 100, 100, 190, 190, $verdeIyem);
            $this->centrarTexto($img, 'GY', 100, 90, $blanco, 5);
            $this->centrarTexto($img, 'YUCATAN', 100, 130, $blanco, 2);
        } else {
            // Rectángulo verde con "IYEM"
            imagefilledrectangle($img, 10, 60, 190, 140, $verdeIyem);
            $this->centrarTexto($img, 'IYEM', 100, 90, $blanco, 5);
        }

        imagepng($img, $destino);
        imagedestroy($img);
    }

    private function centrarTexto($img, string $texto, int $cx, int $cy, int $color, int $fontSize): void
    {
        $ancho = imagefontwidth($fontSize) * strlen($texto);
        $alto  = imagefontheight($fontSize);
        imagestring($img, $fontSize, (int) ($cx - $ancho / 2), (int) ($cy - $alto / 2), $texto, $color);
    }
}
