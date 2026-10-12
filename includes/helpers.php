<?php
/**
 * BiblioTech — Funciones comunes reutilizables.
 *
 * Agrupa el escapado de salida, la construcción de URLs, el renderizado de
 * vistas, el formateo de datos y los mensajes flash.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------
 * Salida segura
 * ------------------------------------------------------------------ */

/**
 * Escapa texto para mostrarlo en HTML y evitar XSS.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ------------------------------------------------------------------
 * URLs
 * ------------------------------------------------------------------ */

/**
 * Carpeta base de la aplicación (funciona en raíz o en subcarpeta).
 * Ej.: si el proyecto está en /BiblioTech/index.php devuelve "/BiblioTech".
 */
function base_url(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $directorio = str_replace('\\', '/', dirname($script));

    if ($directorio === '/' || $directorio === '.' || $directorio === '') {
        $base = '';

        return $base;
    }

    $base = rtrim($directorio, '/');

    return $base;
}

/**
 * URL interna a partir del nombre de ruta declarado en config/routes.php.
 *
 * @param array<string, mixed> $parametros Parámetros adicionales de la URL.
 */
function url(string $ruta = '', array $parametros = []): string
{
    $url = base_url() . '/index.php';

    if ($ruta !== '') {
        $parametros = ['ruta' => $ruta] + $parametros;
    }

    if ($parametros !== []) {
        $url .= '?' . http_build_query($parametros);
    }

    return $url;
}

/** URL de un recurso dentro de public/. */
function asset(string $ruta): string
{
    return base_url() . '/public/' . ltrim($ruta, '/');
}

/** Redirige a una ruta interna y detiene la ejecución. */
function redirigir(string $ruta = '', array $parametros = []): never
{
    header('Location: ' . url($ruta, $parametros));
    exit;
}

/**
 * Indica si una ruta está registrada en config/routes.php.
 *
 * Permite que las vistas no muestren enlaces a módulos que todavía no
 * existen, en lugar de ofrecer botones que terminan en un error 404.
 */
function ruta_existe(string $ruta): bool
{
    static $rutas = null;

    if ($rutas === null) {
        $rutas = require BASE_PATH . '/config/routes.php';
    }

    return array_key_exists(trim($ruta, '/'), $rutas);
}

/** Indica si la ruta actual coincide con la ruta indicada. */
function ruta_activa(string $ruta): bool
{
    return ruta_actual() === $ruta;
}

/** Ruta solicitada actualmente. */
function ruta_actual(): string
{
    return trim((string) ($_GET['ruta'] ?? ''), '/');
}

/* ------------------------------------------------------------------
 * Vistas
 * ------------------------------------------------------------------ */

/**
 * Renderiza una vista y devuelve el HTML resultante.
 * Los puntos en el nombre representan carpetas: 'admin.libros.index'.
 *
 * @param array<string, mixed> $datos
 */
function render_vista(string $vista, array $datos = []): string
{
    /*
     * Los parámetros usan nombres reservados (prefijo __) a propósito: si se
     * llamaran $datos, el extract() con EXTR_SKIP no podría crear una variable
     * de vista llamada $datos y el formulario perdería sus valores.
     */
    $__archivoVista = BASE_PATH . '/views/' . str_replace('.', '/', $vista) . '.php';

    if (!is_file($__archivoVista)) {
        throw new RuntimeException("No se encontró la vista: {$vista}");
    }

    $__variablesVista = $datos;
    unset($datos, $vista);

    extract($__variablesVista, EXTR_SKIP);

    ob_start();
    require $__archivoVista;

    return (string) ob_get_clean();
}

/**
 * Renderiza una vista dentro de una plantilla.
 *
 * @param array<string, mixed> $datos
 */
function render(string $vista, array $datos = [], ?string $layout = 'layouts.app'): void
{
    $contenido = render_vista($vista, $datos);

    if ($layout === null) {
        echo $contenido;

        return;
    }

    echo render_vista($layout, array_merge($datos, ['contenido' => $contenido]));
}

/* ------------------------------------------------------------------
 * Entradas
 * ------------------------------------------------------------------ */

/** Devuelve un valor de $_GET o $_POST ya recortado. */
function entrada(string $clave, ?string $porDefecto = null): ?string
{
    $valor = $_POST[$clave] ?? $_GET[$clave] ?? null;

    if ($valor === null) {
        return $porDefecto;
    }

    if (is_array($valor)) {
        return $porDefecto;
    }

    return trim((string) $valor);
}

/** Devuelve un entero de $_GET o $_POST. */
function entrada_int(string $clave, int $porDefecto = 0): int
{
    $valor = entrada($clave);

    return ($valor === null || $valor === '') ? $porDefecto : (int) $valor;
}

/** Indica si la petición actual es POST. */
function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ------------------------------------------------------------------
 * Formato de datos
 * ------------------------------------------------------------------ */

/** Importe en soles peruanos. Ej.: 9 => "S/ 9.00". */
function dinero(float|int|string|null $monto): string
{
    return 'S/ ' . number_format((float) $monto, 2, '.', ',');
}

/** Fecha en formato dd/mm/aaaa. */
function fecha_corta(?string $fecha): string
{
    if ($fecha === null || $fecha === '' || $fecha === '0000-00-00') {
        return '—';
    }

    $marca = strtotime($fecha);

    return $marca === false ? '—' : date('d/m/Y', $marca);
}

/** Fecha y hora en formato dd/mm/aaaa HH:MM. */
function fecha_hora(?string $fecha): string
{
    if ($fecha === null || $fecha === '' || $fecha === '0000-00-00 00:00:00') {
        return '—';
    }

    $marca = strtotime($fecha);

    return $marca === false ? '—' : date('d/m/Y H:i', $marca);
}

/** Etiqueta legible del mes y año de una fecha. Ej.: "Octubre 2026". */
function mes_largo(?string $fecha): string
{
    $meses = [
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];

    $marca = $fecha === null ? time() : (strtotime($fecha) ?: time());

    return $meses[(int) date('n', $marca)] . ' ' . date('Y', $marca);
}

/**
 * Fecha y hora legible en español. Ej.: "12/10/2026 09:30".
 */
function fecha_larga(?string $fecha): string
{
    return fecha_hora($fecha);
}

/** Recorta un texto largo respetando los caracteres UTF-8. */
function truncar(?string $texto, int $largo = 120): string
{
    $texto = trim((string) $texto);

    if ($texto === '') {
        return '';
    }

    if (mb_strlen($texto, 'UTF-8') <= $largo) {
        return $texto;
    }

    return rtrim(mb_substr($texto, 0, $largo - 1, 'UTF-8')) . '…';
}

/**
 * Importe de multa por los días de retraso, según las reglas configuradas.
 */
function calcular_multa(int $diasRetraso): float
{
    $cfg = config('prestamos', []);
    $tarifa = (float) ($cfg['multa_por_dia'] ?? 1.5);
    $gracia = (int) ($cfg['dias_gracia'] ?? 0);
    $diasCobrables = max(0, $diasRetraso - $gracia);

    return round($diasCobrables * $tarifa, 2);
}

/* ------------------------------------------------------------------
 * Presentación de roles y estados
 * ------------------------------------------------------------------ */

/** Nombre visible de un rol. */
function nombre_rol(?string $rol): string
{
    return match ($rol) {
        'administrador' => 'Administrador',
        'bibliotecario' => 'Bibliotecario',
        'estudiante'    => 'Estudiante',
        default         => 'Usuario',
    };
}

/** Nombre visible del estado de un usuario. */
function nombre_estado_usuario(?string $estado): string
{
    return $estado === 'bloqueado' ? 'Bloqueado' : 'Activo';
}

/** Clase CSS del distintivo según el estado recibido. */
function clase_badge(?string $estado): string
{
    return match ($estado) {
        'activo', 'disponible', 'pagada', 'confirmada', 'devuelto' => 'badge badge--ok',
        'pendiente'                                                => 'badge badge--alerta',
        'vencido', 'bloqueado', 'agotado'                          => 'badge badge--peligro',
        'cancelado', 'expirada', 'cancelada', 'condonada'          => 'badge badge--neutro',
        default                                                    => 'badge',
    };
}

/** Clase CSS de la tarjeta de stock (verde con ejemplares, rojo sin ellos). */
function clase_stock(int $disponible, int $total): string
{
    if ($disponible <= 0) {
        return 'stock stock--agotado';
    }

    // Quedan menos de la mitad de los ejemplares: se resalta en ámbar.
    return ($disponible * 2) <= $total ? 'stock stock--parcial' : 'stock stock--ok';
}

/* ------------------------------------------------------------------
 * Mensajes flash
 * ------------------------------------------------------------------ */

/** Guarda un mensaje que se mostrará en la siguiente petición. */
function flash(string $tipo, string $mensaje): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Recupera y limpia los mensajes pendientes. */
function flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $mensajes;
}

/**
 * Guarda los datos enviados y los errores de validación para repintar el
 * formulario tras una redirección.
 *
 * @param array<string, mixed> $datos
 * @param array<string, string> $errores
 */
function recordar_entrada(array $datos, array $errores = []): void
{
    $_SESSION['entrada_previa'] = $datos;
    $_SESSION['errores_previos'] = $errores;
}

/** Recupera y limpia los datos del formulario anterior. */
function entrada_previa(): array
{
    $datos = $_SESSION['entrada_previa'] ?? [];
    unset($_SESSION['entrada_previa']);

    return $datos;
}

/** Recupera y limpia los errores de validación anteriores. */
function errores_previos(): array
{
    $errores = $_SESSION['errores_previos'] ?? [];
    unset($_SESSION['errores_previos']);

    return $errores;
}

/* ------------------------------------------------------------------
 * Portadas
 * ------------------------------------------------------------------ */

/**
 * URL de la portada de un libro.
 *
 * Admite tanto una portada subida a uploads/portadas como una URL externa
 * (los registros existentes usan Open Library).
 */
function portada_url(?string $archivo): string
{
    if ($archivo === null || trim($archivo) === '') {
        return asset('assets/img/portada.svg');
    }

    if (preg_match('#^https?://#i', $archivo) === 1) {
        return $archivo;
    }

    return base_url() . '/' . trim((string) config('uploads.carpeta', 'uploads/portadas'), '/')
        . '/' . rawurlencode(basename($archivo));
}

/* ------------------------------------------------------------------
 * Menús por rol
 * ------------------------------------------------------------------ */

/**
 * Elementos del menú lateral según el rol del usuario autenticado.
 *
 * @return array<int, array{etiqueta: string, ruta: string}>
 */
function menu_rol(?array $usuario): array
{
    if ($usuario === null) {
        return [];
    }

    return match ($usuario['rol']) {
        'administrador' => [
            ['etiqueta' => 'Dashboard',      'ruta' => 'admin/dashboard'],
            ['etiqueta' => 'Libros',         'ruta' => 'admin/libros'],
            ['etiqueta' => 'Categorías',     'ruta' => 'admin/categorias'],
            ['etiqueta' => 'Usuarios',       'ruta' => 'admin/usuarios'],
            ['etiqueta' => 'Roles y permisos', 'ruta' => 'admin/roles'],
            ['etiqueta' => 'Reservas',       'ruta' => 'admin/reservas'],
            ['etiqueta' => 'Préstamos',      'ruta' => 'admin/prestamos'],
            ['etiqueta' => 'Multas',         'ruta' => 'admin/multas'],
            ['etiqueta' => 'Reportes',       'ruta' => 'admin/reportes'],
        ],
        'bibliotecario' => [
            ['etiqueta' => 'Panel',          'ruta' => 'bibliotecario/panel'],
            ['etiqueta' => 'Libros',         'ruta' => 'bibliotecario/libros'],
            ['etiqueta' => 'Préstamos',      'ruta' => 'bibliotecario/prestamos'],
            ['etiqueta' => 'Reservas',       'ruta' => 'bibliotecario/reservas'],
            ['etiqueta' => 'Multas',         'ruta' => 'bibliotecario/multas'],
        ],
        default => [
            ['etiqueta' => 'Inicio',         'ruta' => 'estudiante/panel'],
            ['etiqueta' => 'Catálogo',       'ruta' => 'catalogo'],
            ['etiqueta' => 'Mis préstamos',  'ruta' => 'estudiante/prestamos'],
            ['etiqueta' => 'Mis reservas',   'ruta' => 'estudiante/reservas'],
            ['etiqueta' => 'Mis multas',     'ruta' => 'estudiante/multas'],
        ],
    };
}

/**
 * Prefijo de rutas que corresponde al rol indicado (o al autenticado).
 *
 * Permite compartir un mismo módulo entre el panel administrativo y el del
 * bibliotecario sin duplicar controladores ni vistas.
 */
function prefijo_rol(?string $rol = null): string
{
    return match ($rol ?? rol_actual()) {
        'administrador' => 'admin',
        'bibliotecario' => 'bibliotecario',
        default         => 'estudiante',
    };
}

/** Título de la barra lateral según el rol. */
function titulo_panel(?array $usuario): string
{
    return match ($usuario['rol'] ?? null) {
        'administrador' => 'Administración',
        'bibliotecario' => 'Panel biblioteca',
        'estudiante'    => 'Mi cuenta',
        default         => 'Menú',
    };
}
