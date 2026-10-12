<?php
/**
 * BiblioTech — Arranque del núcleo.
 *
 * Carga helpers, sesión, CSRF y enrutador, registra la autocarga de clases y
 * configura el manejo de errores. Debe incluirse una sola vez.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/sesion.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/subidas.php';
require_once BASE_PATH . '/includes/router.php';

/*
 * Autocarga de controladores, modelos e includes por nombre de clase.
 * Evita tener que listar cada `require` en cada archivo.
 */
spl_autoload_register(static function (string $clase): void {
    $carpetas = ['controllers', 'models', 'includes'];
    $nombre = basename(str_replace('\\', '/', $clase));

    foreach ($carpetas as $carpeta) {
        $archivo = BASE_PATH . '/' . $carpeta . '/' . $nombre . '.php';
        if (is_file($archivo)) {
            require_once $archivo;

            return;
        }
    }
});

/** Muestra u oculta el detalle técnico de los errores. */
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

/** Registro de errores en archivo para no exponerlos al usuario. */
$rutaErrores = BASE_PATH . '/storage';
if (is_dir($rutaErrores)) {
    ini_set('log_errors', '1');
    ini_set('error_log', $rutaErrores . '/php-error.log');
}

/**
 * Cualquier excepción no controlada se convierte en una página 500 legible:
 * nunca se muestra un volcado de error de PHP al usuario final.
 */
set_exception_handler(static function (Throwable $e): void {
    error_log(sprintf('[BiblioTech] %s en %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));

    if (!headers_sent()) {
        http_response_code(500);
    }

    try {
        echo render_vista('errors/500', [
            'detalle' => APP_DEBUG ? $e->getMessage() : null,
        ]);
    } catch (Throwable) {
        echo '<h1>Error interno del sistema</h1><p>Intenta nuevamente más tarde.</p>';
    }
});

configurar_sesion();
