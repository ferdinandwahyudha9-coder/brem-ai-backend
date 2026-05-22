<?php
// HAPUS FILE INI SETELAH MIGRATION SELESAI!
// Akses via: https://namadomain.rf.gd/migrate.php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Artisan::call('migrate', ['--force' => true]);
    echo '<pre style="background:#1a1a1a;color:#4ade80;padding:20px;font-size:14px;">';
    echo "✅ Migration berhasil!\n\n";
    echo Artisan::output();
    echo '</pre>';

    Artisan::call('storage:link');
    echo '<pre style="background:#1a1a1a;color:#4ade80;padding:20px;font-size:14px;">';
    echo "✅ Storage link berhasil!\n";
    echo '</pre>';
} catch (Exception $e) {
    echo '<pre style="background:#1a1a1a;color:#f87171;padding:20px;font-size:14px;">';
    echo "❌ Error: " . $e->getMessage();
    echo '</pre>';
}

echo '<p style="color:red;font-weight:bold;">⚠️ HAPUS FILE migrate.php INI SEKARANG!</p>';
