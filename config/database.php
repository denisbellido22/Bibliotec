<?php
/**
 * BiblioTech — Conexión a la base de datos existente `Bibliotec`.
 *
 * Estrategia de credenciales (sección 7 de las instrucciones de entrega):
 *   1. Variables de entorno (BIBLIOTECH_DB_*), ideales en producción.
 *   2. Archivo privado `config/database.local.php`, NO versionado.
 *   3. Valores por defecto de XAMPP (root sin contraseña).
 *
 * Ninguna contraseña real se guarda en este archivo.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

/**
 * Devuelve la configuración completa del sistema (cargada una sola vez).
 *
 * @return array<string, mixed>
 */
function config(?string $clave = null, mixed $porDefecto = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';

        // Credenciales privadas locales: sobreescriben solo la sección `db`.
        $local = __DIR__ . '/database.local.php';
        if (is_file($local)) {
            $personalizado = require $local;
            if (is_array($personalizado)) {
                $config['db'] = array_merge($config['db'], $personalizado);
            }
        }
    }

    if ($clave === null) {
        return $config;
    }

    // Permite notación con puntos: config('db.nombre') => 'Bibliotec'.
    $valor = $config;
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $porDefecto;
        }
        $valor = $valor[$parte];
    }

    return $valor;
}

/**
 * Conexión PDO única (patrón singleton) a la base de datos `Bibliotec`.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = config('db');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['puerto'],
        $cfg['nombre'],
        $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, (string) $cfg['usuario'], (string) $cfg['password'], [
            // Los errores se informan como excepciones: nunca se silencian.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consultas realmente preparadas en el servidor (anti inyección SQL).
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    } catch (PDOException $e) {
        // Mensaje claro sin exponer usuario ni contraseña.
        $mensaje = 'No se pudo conectar con la base de datos "' . $cfg['nombre'] . '". '
            . 'Verifica que MySQL/MariaDB esté iniciado en XAMPP y que la base de datos exista.';

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, '[ERROR] ' . $mensaje . PHP_EOL . 'Detalle: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="es"><meta charset="utf-8">'
            . '<title>Error de conexión</title>'
            . '<body style="font-family:system-ui,sans-serif;background:#FBF8EC;color:#2B2B2B;padding:3rem">'
            . '<h1 style="color:#6E2A4E">Error de conexión</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</body></html>';
        exit;
    }

    return $pdo;
}

/**
 * Comprueba que la conexión responde y que las tablas esperadas existen.
 *
 * @return array<string, int> Cantidad de registros por tabla.
 */
function db_resumen_tablas(): array
{
    $tablas = ['categorias', 'libros', 'usuarios', 'prestamos', 'reservas', 'multas'];
    $resumen = [];

    foreach ($tablas as $tabla) {
        $resumen[$tabla] = (int) db()->query('SELECT COUNT(*) FROM `' . $tabla . '`')->fetchColumn();
    }

    return $resumen;
}
