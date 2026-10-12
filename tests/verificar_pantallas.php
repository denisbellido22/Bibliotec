<?php
/**
 * BiblioTech — Verificación de contenido real en las pantallas clave.
 *
 * Comprueba que cada panel muestre datos de la base de datos y no una página
 * vacía o un error, iniciando sesión con los tres roles de demostración.
 *
 * Uso:  php tests/verificar_pantallas.php
 *
 * Solo realiza peticiones GET y de inicio de sesión: no modifica ningún dato.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/config/database.php';

$base = 'http://localhost/BiblioTech/index.php';

function bajar(string $url, string $jar, ?array $post = null): string
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $r = (string) curl_exec($c);
    curl_close($c);
    return $r;
}

function entrar(string $email, string $nombre): string
{
    global $base;
    $jar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "bt_cont_{$nombre}.cookie";
    @unlink($jar);
    $html = bajar($base . '?ruta=login', $jar);
    preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m);
    bajar($base . '?ruta=login', $jar, [
        'csrf_token' => $m[1] ?? '',
        'email'      => $email,
        'password'   => 'password',
    ]);
    return $jar;
}

$ok = 0;
$fallos = [];

function revisar(string $titulo, string $html, array $esperados): void
{
    global $ok, $fallos;
    $faltan = [];
    foreach ($esperados as $texto) {
        if (!str_contains($html, $texto)) {
            $faltan[] = $texto;
        }
    }
    if ($faltan === []) {
        $ok++;
        echo "  [OK]    {$titulo}" . PHP_EOL;
    } else {
        $fallos[] = "{$titulo} — no aparece: " . implode(', ', $faltan);
        echo "  [FALLO] {$titulo} — falta: " . implode(', ', $faltan) . PHP_EOL;
    }
}

echo PHP_EOL . '== Administrador ==' . PHP_EOL;
$adm = entrar('admin@bibliotech.test', 'adm');
revisar('El dashboard muestra indicadores reales', bajar($base . '?ruta=admin/dashboard', $adm), [
    'Reporte general', 'Catálogo', 'Préstamos activos', 'Estudiantes', 'Bibliotecarios',
]);
revisar('Gestión de libros lista el catálogo', bajar($base . '?ruta=admin/libros', $adm), [
    'Clean Code', 'Cien Años de Soledad', 'Ingeniería de Software',
]);
revisar('Gestión de categorías lista las categorías', bajar($base . '?ruta=admin/categorias', $adm), [
    'Literatura', 'Historia',
]);
revisar('Gestión de usuarios lista las cuentas', bajar($base . '?ruta=admin/usuarios', $adm), [
    'admin@bibliotech.test', 'biblio@bibliotech.test', 'carlos@bibliotech.test',
]);
revisar('Roles y permisos muestra la matriz', bajar($base . '?ruta=admin/roles', $adm), [
    'administrador', 'bibliotecario', 'estudiante',
]);
revisar('Préstamos muestra los registros', bajar($base . '?ruta=admin/prestamos', $adm), [
    'Préstamo',
]);
revisar('Reservas muestra los registros', bajar($base . '?ruta=admin/reservas', $adm), ['Reserva']);
revisar('Multas muestra los registros', bajar($base . '?ruta=admin/multas', $adm), ['Multa']);
revisar('Reportes muestra estadísticas reales', bajar($base . '?ruta=admin/reportes', $adm), [
    'Reportes y estadísticas', 'Préstamos por mes', 'Libros más prestados',
    'Préstamos por categoría', 'Catálogo y disponibilidad por categoría',
]);
revisar('El detalle de un libro muestra su ISBN', bajar($base . '?ruta=admin/libros/ver&id=1', $adm), [
    'ISBN',
]);

echo PHP_EOL . '== Bibliotecario ==' . PHP_EOL;
$bib = entrar('biblio@bibliotech.test', 'bib');
revisar('Panel de la bibliotecaria con su nombre', bajar($base . '?ruta=bibliotecario/panel', $bib), [
    'Lucía Fernández',
]);
revisar('Catálogo en modo lectura', bajar($base . '?ruta=bibliotecario/libros', $bib), [
    'sólo lectura', 'registros encontrados', 'Clean Code',
]);
revisar('Préstamos del bibliotecario', bajar($base . '?ruta=bibliotecario/prestamos', $bib), ['Préstamo']);
revisar('Reservas del bibliotecario', bajar($base . '?ruta=bibliotecario/reservas', $bib), ['Reserva']);
revisar('Multas del bibliotecario', bajar($base . '?ruta=bibliotecario/multas', $bib), ['Multa']);

echo PHP_EOL . '== Estudiante ==' . PHP_EOL;
$est = entrar('carlos@bibliotech.test', 'est');
revisar('Panel del estudiante con su nombre', bajar($base . '?ruta=estudiante/panel', $est), [
    'Carlos Ramírez',
]);
revisar('Mis préstamos muestra el resumen y la tabla', bajar($base . '?ruta=estudiante/prestamos', $est), [
    'Mis préstamos', 'abiertos', 'con retraso', 'devueltos', 'Devolución esperada',
]);
revisar('Mis reservas muestra el estado de cada reserva', bajar($base . '?ruta=estudiante/reservas', $est), [
    'Mis reservas', 'Estado', 'Libro',
]);
revisar('Mis multas muestra los totales de pago', bajar($base . '?ruta=estudiante/multas', $est), [
    'Mis multas', 'Pendiente de pago', 'Ya pagado', 'Condonadas',
]);

echo PHP_EOL . '== Público ==' . PHP_EOL;
revisar('El catálogo público muestra los libros', bajar($base . '?ruta=catalogo', ''), [
    'Clean Code', 'Cien Años de Soledad',
]);
revisar('La ficha de un libro muestra disponibilidad', bajar($base . '?ruta=catalogo/ver&id=1', ''), [
    'Disponible',
]);

echo PHP_EOL . str_repeat('=', 78) . PHP_EOL;
printf("Pantallas correctas: %d\n", $ok);
printf("Pantallas fallidas:  %d\n", count($fallos));
foreach ($fallos as $f) {
    echo '  - ' . $f . PHP_EOL;
}
echo $fallos === [] ? "RESULTADO: TODAS LAS PANTALLAS MUESTRAN DATOS REALES\n" : "RESULTADO: HAY FALLOS\n";
