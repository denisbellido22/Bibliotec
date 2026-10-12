<?php
/**
 * BiblioTech — Sesiones y autenticación (RF01, RNF02).
 *
 * La contraseña se verifica con password_verify() contra el hash almacenado en
 * `usuarios.password_hash`. La sesión se regenera al iniciar sesión para evitar
 * fijación de sesión y caduca por inactividad.
 */

declare(strict_types=1);

/** Minutos de inactividad permitidos antes de cerrar la sesión. */
const SESION_INACTIVIDAD = 120;

/**
 * Configura e inicia la sesión con cookies endurecidas.
 */
function configurar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $porHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $porHttps,
        'httponly' => true,   // La cookie no es accesible desde JavaScript.
        'samesite' => 'Lax',  // Mitiga CSRF en navegaciones cruzadas.
    ]);

    session_name('BIBLIOTECH_SESION');
    session_start();

    cerrar_sesion_por_inactividad();
}

/**
 * Cierra la sesión si superó el tiempo máximo de inactividad.
 */
function cerrar_sesion_por_inactividad(): void
{
    if (!isset($_SESSION['usuario'])) {
        $_SESSION['ultima_actividad'] = time();

        return;
    }

    $ultima = (int) ($_SESSION['ultima_actividad'] ?? time());

    if (time() - $ultima > SESION_INACTIVIDAD * 60) {
        limpiar_sesion();
        flash('info', 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.');

        return;
    }

    $_SESSION['ultima_actividad'] = time();
}

/**
 * Busca un usuario por correo y valida la contraseña.
 *
 * @return array<string, mixed>|null Datos del usuario o null si no coincide.
 */
function autenticar(string $email, string $password): ?array
{
    $sql = 'SELECT id, nombre, email, password_hash, rol, estado
              FROM usuarios
             WHERE email = :email
             LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario === false) {
        return null;
    }

    if (!password_verify($password, (string) $usuario['password_hash'])) {
        return null;
    }

    // Un usuario bloqueado no puede acceder, aunque su contraseña sea correcta.
    if ($usuario['estado'] === 'bloqueado') {
        return null;
    }

    return $usuario;
}

/**
 * Registra al usuario en la sesión.
 *
 * @param array<string, mixed> $usuario
 */
function iniciar_sesion(array $usuario): void
{
    // Evita la fijación de sesión: nuevo identificador tras autenticarse.
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nombre' => (string) $usuario['nombre'],
        'email'  => (string) $usuario['email'],
        'rol'    => (string) $usuario['rol'],
        'estado' => (string) $usuario['estado'],
    ];
    $_SESSION['ultima_actividad'] = time();
}

/** Borra todos los datos de la sesión actual. */
function limpiar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    session_destroy();
}

/** Cierra la sesión y arranca una limpia para poder mostrar mensajes. */
function cerrar_sesion(): void
{
    limpiar_sesion();

    session_name('BIBLIOTECH_SESION');
    session_start();
    session_regenerate_id(true);
}

/**
 * Datos del usuario autenticado, o null si no hay sesión.
 *
 * @return array<string, mixed>|null
 */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** Indica si hay un usuario autenticado. */
function esta_autenticado(): bool
{
    return isset($_SESSION['usuario']);
}

/** Rol del usuario autenticado, o null. */
function rol_actual(): ?string
{
    return $_SESSION['usuario']['rol'] ?? null;
}

/** Identificador del usuario autenticado, o null. */
function id_usuario_actual(): ?int
{
    return isset($_SESSION['usuario']['id']) ? (int) $_SESSION['usuario']['id'] : null;
}

/** Indica si el usuario autenticado tiene alguno de los roles indicados. */
function tiene_rol(string ...$roles): bool
{
    $rol = rol_actual();

    return $rol !== null && in_array($rol, $roles, true);
}

/**
 * Exige que la sesión pertenezca a uno de los roles indicados.
 * Se usa al inicio de cada acción protegida, además del enrutador.
 */
function requerir_rol(string ...$roles): void
{
    if (!esta_autenticado()) {
        flash('error', 'Debes iniciar sesión para continuar.');
        redirigir('login');
    }

    if ($roles !== [] && !tiene_rol(...$roles)) {
        responder_error(403, 'Tu rol no tiene permisos para acceder a esta sección.');
    }
}

/** Exige una sesión activa, sin importar el rol. */
function requerir_sesion(): void
{
    if (!esta_autenticado()) {
        flash('error', 'Debes iniciar sesión para continuar.');
        redirigir('login');
    }
}
