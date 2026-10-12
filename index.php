<?php
/**
 * BiblioTech — Punto de entrada único (front controller).
 *
 * Todas las peticiones pasan por aquí. El enrutador resuelve `?ruta=...`
 * contra config/routes.php, verifica el rol en el servidor y sólo entonces
 * ejecuta el controlador correspondiente.
 *
 * URL de ejemplo: http://localhost/BiblioTech/index.php?ruta=catalogo
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);

/**
 * Muestra el detalle técnico de los errores.
 * Cámbialo a false al publicar el proyecto.
 */
define('APP_DEBUG', true);

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/includes/bootstrap.php';

despachar();
