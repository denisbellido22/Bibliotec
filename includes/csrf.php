<?php
/**
 * BiblioTech — Protección CSRF.
 *
 * Cada formulario que modifica datos incluye un token ligado a la sesión.
 * El enrutador rechaza cualquier POST cuyo token no coincida.
 */

declare(strict_types=1);

/** Devuelve (creando si hace falta) el token CSRF de la sesión. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/** Campo oculto listo para insertar en un formulario. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Comprueba el token recibido por POST contra el de la sesión.
 * Usa hash_equals para comparar en tiempo constante.
 */
function csrf_verificar(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';

    if (!is_string($enviado) || $enviado === '') {
        return false;
    }

    $esperado = $_SESSION['csrf_token'] ?? '';

    if ($esperado === '') {
        return false;
    }

    return hash_equals($esperado, $enviado);
}
