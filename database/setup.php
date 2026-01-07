<?php
define('BASEPATH', true);
require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/init.php';
require_once SYSTEM_PATH . 'helpers/functions.php';

echo "=== Configuración Inicial del Proyecto ===\n\n";

try {
    echo "1. Ejecutando migration de usuarios...\n";
    require_once __DIR__ . '/migrations/create_usuarios_table.php';
    $migration = (require_once __DIR__ . '/migrations/create_usuarios_table.php');
    $migration->up();
    echo "   ✅ Tabla usuarios creada\n\n";

    echo "2. Ejecutando seed de usuarios...\n";
    require_once __DIR__ . '/seeds/usuarios_seed.php';
    $seed = (require_once __DIR__ . '/seeds/usuarios_seed.php');
    $seed->run();
    echo "   ✅ Seed completado\n\n";

    echo "=== Configuración Completada Exitosamente ===\n";
    echo "\nCredenciales de prueba:\n";
    echo "  Email: admin@ejemplo.com\n";
    echo "  Password: admin123\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
