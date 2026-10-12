<?php
/**
 * Verificación de la conexión PDO con la base de datos existente `Bibliotec`.
 *
 * Uso (desde la raíz del proyecto):
 *   php tests/test_conexion.php
 *
 * No modifica datos: sólo lee estructura y metadatos.
 */

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$fallos = 0;

function ok(string $mensaje): void
{
    echo "  [OK]   {$mensaje}" . PHP_EOL;
}

function fallo(string $mensaje): void
{
    global $fallos;
    $fallos++;
    echo "  [FALLA] {$mensaje}" . PHP_EOL;
}

echo PHP_EOL . '== Conexión a la base de datos BiblioTech ==' . PHP_EOL;

$cfg = config('db');
echo "Motor   : MySQL/MariaDB" . PHP_EOL;
echo "Host    : {$cfg['host']}:{$cfg['puerto']}" . PHP_EOL;
echo "Base    : {$cfg['nombre']}" . PHP_EOL;
echo "Usuario : {$cfg['usuario']}" . PHP_EOL . PHP_EOL;

// 1) Conexión efectiva.
$pdo = db();
ok('Conexion PDO establecida.');

$version = $pdo->query('SELECT VERSION()')->fetchColumn();
ok("Version del servidor: {$version}");

// 2) Nombre real de la base de datos a la que estamos conectados.
$actual = $pdo->query('SELECT DATABASE()')->fetchColumn();
if (strcasecmp((string) $actual, $cfg['nombre']) === 0) {
    ok("Base de datos activa confirmada: {$actual}");
} else {
    fallo("Se esperaba la base de datos '{$cfg['nombre']}' pero la conexion usa '{$actual}'.");
}

// 3) Tablas esperadas segun el volcado original.
$esperadas = ['categorias', 'libros', 'multas', 'prestamos', 'reservas', 'usuarios'];
$sql = 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME';
$existentes = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

foreach ($esperadas as $tabla) {
    if (in_array($tabla, $existentes, true)) {
        ok("Tabla presente: {$tabla}");
    } else {
        fallo("Falta la tabla obligatoria: {$tabla}");
    }
}

// 4) Consultas preparadas reales (no emuladas) => proteccion anti inyeccion SQL.
$emula = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
if ($emula === false || $emula === 0) {
    ok('Consultas preparadas nativas habilitadas (ATTR_EMULATE_PREPARES = false).');
} else {
    fallo('Las consultas preparadas estan emuladas: riesgo de inyeccion SQL.');
}

// 5) Lectura de datos reales conservados.
echo PHP_EOL . '-- Registros existentes (no se modifican) --' . PHP_EOL;
foreach (db_resumen_tablas() as $tabla => $cantidad) {
    printf("  %-12s %d%s", $tabla, $cantidad, PHP_EOL);
}

// 6) Relaciones entre tablas (llaves foraneas declaradas).
echo PHP_EOL . '-- Llaves foraneas detectadas --' . PHP_EOL;
$fk = $pdo->query(
    'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
       FROM information_schema.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
      ORDER BY TABLE_NAME'
)->fetchAll();

if ($fk === []) {
    fallo('No se detectaron llaves foraneas.');
} else {
    foreach ($fk as $r) {
        printf(
            "  %s.%s -> %s.%s%s",
            $r['TABLE_NAME'],
            $r['COLUMN_NAME'],
            $r['REFERENCED_TABLE_NAME'],
            $r['REFERENCED_COLUMN_NAME'],
            PHP_EOL
        );
    }
}

echo PHP_EOL;
if ($fallos === 0) {
    echo '== RESULTADO: TODAS LAS COMPROBACIONES PASARON ==' . PHP_EOL;
    exit(0);
}

echo "== RESULTADO: {$fallos} comprobacion(es) fallaron ==" . PHP_EOL;
exit(1);
