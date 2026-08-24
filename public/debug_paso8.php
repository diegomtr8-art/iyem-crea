<?php
// Temporary debug script - DELETE after use
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

try {
    $schema = Illuminate\Support\Facades\Schema::getFacadeRoot();
    $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('avales_solicitud', 'bienes_muebles');
    echo json_encode([
        'has_bienes_muebles' => $hasColumn,
        'avales_columns' => \Illuminate\Support\Facades\Schema::getColumnListing('avales_solicitud'),
        'php_version' => PHP_VERSION,
    ]);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)]);
}
