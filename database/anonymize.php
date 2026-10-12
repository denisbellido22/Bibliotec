<?php
/**
 * BiblioTech — Generador del volcado SQL apto para publicar en GitHub.
 *
 * El volcado original `database/bibliotech.sql` se conserva INTACTO y fuera del
 * repositorio porque contiene correos y nombres de personas reales, además de
 * hashes de contraseña. Este script produce una copia equivalente que:
 *
 *   - Conserva la estructura completa (tablas, índices, llaves foráneas).
 *   - Conserva categorías y libros sin cambios.
 *   - Reemplaza nombres y correos de usuarios por datos de demostración.
 *   - Reemplaza todos los hashes por el hash de demostración de `password`.
 *
 * Uso:
 *   php database/anonymize.php
 *
 * No toca la base de datos ni el archivo original.
 */

declare(strict_types=1);

$origen   = __DIR__ . '/bibliotech.sql';
$destino  = __DIR__ . '/bibliotech.demo.sql';

if (!is_file($origen)) {
    fwrite(STDERR, "[ERROR] No se encontro el volcado original: {$origen}" . PHP_EOL);
    exit(1);
}

$sql = file_get_contents($origen);
if ($sql === false) {
    fwrite(STDERR, "[ERROR] No se pudo leer el volcado original." . PHP_EOL);
    exit(1);
}

/** Hash bcrypt de la contraseña de demostración `password`. */
$hashDemo = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

/* 1. Nombres y correos reales -> datos de demostración. */
$personas = [
    "'Christian Huayta Q49', 'christianhuaytaq@gmail.com'" => "'Estudiante Cinco Demo', 'estudiante5@bibliotech.test'",
    "'jhonady', 'jhonady@gmail.com'"                       => "'Estudiante Seis Demo', 'estudiante6@bibliotech.test'",
    "'David Delacruz', 'lh588589@gmail.com'"               => "'Bibliotecario Siete Demo', 'biblio7@bibliotech.test'",
    "'Waldo Rusbel Lopez', 'rwaldorusbel@gmail.com'"       => "'Estudiante Ocho Demo', 'estudiante8@bibliotech.test'",
];

$reemplazos = 0;
foreach ($personas as $real => $demo) {
    $sql = str_replace($real, $demo, $sql, $cuenta);
    $reemplazos += $cuenta;
}

/*
 * 2. Cualquier hash bcrypt restante -> hash de demostración.
 *
 * Se usa preg_replace_callback porque en el reemplazo de preg_replace los
 * caracteres `$2` y `$10` del hash se interpretan como retro-referencias y
 * corromperían el valor generado.
 */
$sql = preg_replace_callback(
    '/\$2y\$\d{2}\$[A-Za-z0-9.\/]{53}/',
    static fn (array $coincidencia): string => $hashDemo,
    $sql,
    -1,
    $hashes
);

/* 3. Cabecera que documenta el origen del archivo. */
$cabecera = <<<SQL
-- ==========================================================
-- BiblioTech - Volcado de ESTRUCTURA y DATOS DE DEMOSTRACION
-- ==========================================================
-- Este archivo es una copia anonimizada del volcado de desarrollo:
--   * Nombres y correos de usuarios reemplazados por datos ficticios.
--   * Todos los hashes corresponden a la contrasena de demostracion: password
--   * Categorias y libros se conservan sin cambios.
--
-- Base de datos: Bibliotec
-- Restaurar con:  mysql -u root -p Bibliotec < database/bibliotech.demo.sql
-- ==========================================================

SQL;

$salida = $cabecera . $sql;

if (file_put_contents($destino, $salida) === false) {
    fwrite(STDERR, "[ERROR] No se pudo escribir {$destino}" . PHP_EOL);
    exit(1);
}

printf(
    "Volcado anonimizado generado: %s%s  personas reemplazadas: %d%s  hashes reemplazados: %d%s",
    basename($destino),
    PHP_EOL,
    $reemplazos,
    PHP_EOL,
    (int) $hashes,
    PHP_EOL
);
