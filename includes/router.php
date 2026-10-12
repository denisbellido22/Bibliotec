<?php
/**
 * BiblioTech — Enrutador con control de acceso en el servidor (RNF03).
 *
 * Regla clave: ocultar un botón no protege nada. Aquí se comprueba el rol
 * antes de ejecutar el controlador, de modo que escribir la URL directamente
 * tampoco permite el acceso.
 */

declare(strict_types=1);

/**
 * Resuelve la petición actual y ejecuta el controlador autorizado.
 */
function despachar(): void
{
    $rutas = require BASE_PATH . '/config/routes.php';

    $ruta = ruta_actual();
    $ruta = (string) preg_replace('#/+#', '/', $ruta);

    if (!array_key_exists($ruta, $rutas)) {
        responder_error(404, 'La página solicitada no existe.');

        return;
    }

    [$clase, $metodo, $roles] = array_pad($rutas[$ruta], 3, null);

    // 1) Verificación de acceso por rol ANTES de ejecutar nada.
    if ($roles !== null && !autorizar_roles($roles)) {
        return;
    }

    // 2) Protección CSRF para cualquier petición que modifica datos.
    if (es_post() && !csrf_verificar()) {
        responder_csrf();

        return;
    }

    // 3) Ejecución del controlador.
    if (!class_exists($clase)) {
        responder_error(500, 'El módulo solicitado no está disponible.');

        return;
    }

    $controlador = new $clase();

    if (!method_exists($controlador, $metodo) || !is_callable([$controlador, $metodo])) {
        responder_error(500, 'La acción solicitada no está disponible.');

        return;
    }

    $controlador->{$metodo}();
}

/**
 * Comprueba que el rol del usuario esté autorizado.
 *
 * @param array<int, string> $roles Roles admitidos; ['*'] permite cualquier usuario autenticado.
 */
function autorizar_roles(array $roles): bool
{
    $usuario = usuario_actual();

    if ($usuario === null) {
        flash('error', 'Debes iniciar sesión para acceder a esa sección.');

        $destino = ruta_actual();
        redirigir('login', $destino !== '' ? ['destino' => $destino] : []);

        return false;
    }

    if ($roles === ['*'] || in_array($usuario['rol'], $roles, true)) {
        return true;
    }

    responder_error(403, 'Tu rol no tiene permisos para acceder a esta sección.');

    return false;
}

/**
 * Muestra una página de error con el código HTTP correspondiente.
 */
function responder_error(int $codigo, string $mensaje): void
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }

    $vistas = [
        403 => 'errors/403',
        404 => 'errors/404',
        500 => 'errors/500',
    ];

    $vista = $vistas[$codigo] ?? 'errors/404';

    echo render_vista($vista, ['mensaje' => $mensaje, 'codigo' => $codigo]);
}

/**
 * Rechaza una petición POST sin token CSRF válido.
 *
 * Se responde 403 (prohibido) en lugar de un código inventado: Apache no
 * admite códigos no estándar como 419 y la respuesta degradaría a 500.
 */
function responder_csrf(): void
{
    if (!headers_sent()) {
        http_response_code(403);
    }

    echo render_vista('errors/csrf');
}
