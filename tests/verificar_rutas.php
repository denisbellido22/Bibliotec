<?php
/**
 * BiblioTech — Verificación funcional de rutas por rol.
 *
 * Recorre TODAS las rutas GET del sistema con cada rol y comprueba el código
 * HTTP esperado:
 *   200 -> la función se abre correctamente para ese rol.
 *   302 -> sin sesión, redirige al inicio de sesión.
 *   403 -> con sesión, pero el rol no tiene permiso.
 *
 * Uso:
 *   php tests/verificar_rutas.php
 *   php tests/verificar_rutas.php http://localhost:8099/index.php
 *
 * Solo realiza peticiones GET: no modifica ningún dato.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/config/database.php';

$base = $argv[1] ?? 'http://localhost/BiblioTech/index.php';

/* ---------- Cliente HTTP ---------- */
function peticion(string $url, string $jar, ?array $post = null): array
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 20,
    ]);

    $destino = '';

    if ($post !== null) {
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $crudo = (string) curl_exec($c);
    $codigo = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
    $cabecera = (int) curl_getinfo($c, CURLINFO_HEADER_SIZE);
    curl_close($c);

    $encabezados = substr($crudo, 0, $cabecera);
    if (preg_match('/^Location:\s*(.+)$/mi', $encabezados, $m)) {
        $destino = trim($m[1]);
    }

    return ['codigo' => $codigo, 'cuerpo' => substr($crudo, $cabecera), 'destino' => $destino];
}

function token(string $base, string $jar): string
{
    $r = peticion($base . '?ruta=login', $jar);
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $r['cuerpo'], $m)) {
        return $m[1];
    }
    return '';
}

function sesion(string $base, string $email, string $nombre): string
{
    $jar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "bt_humo_{$nombre}.cookie";
    @unlink($jar);
    peticion($base . '?ruta=login', $jar);
    // La ruta va en la URL; el cuerpo lleva solo los campos del formulario.
    peticion($base . '?ruta=login', $jar, [
        'csrf_token' => token($base, $jar),
        'email'      => $email,
        'password'   => 'password',
    ]);
    return $jar;
}

/* ---------- Rutas GET y su código esperado por rol ---------- */
$rutas = [
    // ruta GET (query), esperado[publico, administrador, bibliotecario, estudiante]
    ''                              => [200, 200, 200, 200],
    'catalogo'                      => [200, 200, 200, 200],
    'catalogo/ver&id=1'             => [200, 200, 200, 200],
    // Con sesión iniciada, `login` redirige al panel del rol (302).
    'login'                         => [200, 302, 302, 302],
    'admin/dashboard'               => [302, 200, 403, 403],
    'admin/libros'                  => [302, 200, 403, 403],
    'admin/libros/crear'            => [302, 200, 403, 403],
    'admin/libros/ver&id=1'         => [302, 200, 403, 403],
    'admin/libros/editar&id=1'      => [302, 200, 403, 403],
    'admin/categorias'              => [302, 200, 403, 403],
    'admin/categorias/crear'        => [302, 200, 403, 403],
    'admin/categorias/editar&id=1'  => [302, 200, 403, 403],
    'admin/usuarios'                => [302, 200, 403, 403],
    'admin/usuarios/crear'          => [302, 200, 403, 403],
    'admin/usuarios/ver&id=2'       => [302, 200, 403, 403],
    'admin/usuarios/editar&id=2'    => [302, 200, 403, 403],
    'admin/roles'                   => [302, 200, 403, 403],
    'admin/prestamos'               => [302, 200, 403, 403],
    'admin/prestamos/crear'         => [302, 200, 403, 403],
    'admin/prestamos/ver&id=1'      => [302, 200, 403, 403],
    'admin/reservas'                => [302, 200, 403, 403],
    'admin/multas'                  => [302, 200, 403, 403],
    'admin/reportes'                => [302, 200, 403, 403],
    'bibliotecario/panel'           => [302, 403, 200, 403],
    'bibliotecario/libros'          => [302, 403, 200, 403],
    'bibliotecario/prestamos'       => [302, 403, 200, 403],
    'bibliotecario/prestamos/crear' => [302, 403, 200, 403],
    'bibliotecario/prestamos/ver&id=1' => [302, 403, 200, 403],
    'bibliotecario/reservas'        => [302, 403, 200, 403],
    'bibliotecario/multas'          => [302, 403, 200, 403],
    'estudiante/panel'              => [302, 403, 403, 200],
    'estudiante/prestamos'          => [302, 403, 403, 200],
    'estudiante/reservas'           => [302, 403, 403, 200],
    'estudiante/multas'             => [302, 403, 403, 200],
];

$roles = [
    'sin sesion'     => '',
    'administrador'  => sesion($base, 'admin@bibliotech.test', 'adm'),
    'bibliotecario'  => sesion($base, 'biblio@bibliotech.test', 'bib'),
    'estudiante'     => sesion($base, 'carlos@bibliotech.test', 'est'),
];

/* ---------- Ejecución ---------- */
$ok = 0;
$fallos = [];

foreach ($rutas as $consulta => $esperados) {
    // La ruta principal se separa de los parámetros por '&'.
    $url = $base . ($consulta === '' ? '' : '?ruta=' . $consulta);

    $linea = sprintf('%-34s', $consulta === '' ? '(inicio)' : $consulta);

    foreach (array_keys($roles) as $i => $rol) {
        $codigo = peticion($url, $roles[$rol])['codigo'];
        $esperado = $esperados[$i];
        $bien = $codigo === $esperado;

        $linea .= sprintf(' %s:%d%s', substr($rol, 0, 3), $codigo, $bien ? '' : "(!{$esperado})");

        if ($bien) {
            $ok++;
        } else {
            $fallos[] = "{$consulta} [{$rol}] esperado {$esperado}, recibido {$codigo}";
        }
    }

    echo $linea . PHP_EOL;
}

echo str_repeat('=', 78) . PHP_EOL;
printf("Comprobaciones correctas: %d\n", $ok);
printf("Comprobaciones fallidas:  %d\n", count($fallos));

foreach ($fallos as $f) {
    echo '  - ' . $f . PHP_EOL;
}

/* ---------- Destino de la redirección de `login` con sesión activa ---------- */
echo PHP_EOL . "Redirección de `login` cuando ya hay sesión:" . PHP_EOL;
$paneles = [
    'administrador' => 'admin/dashboard',
    'bibliotecario' => 'bibliotecario/panel',
    'estudiante'    => 'estudiante/panel',
];

foreach ($paneles as $rol => $panel) {
    $r = peticion($base . '?ruta=login', $roles[$rol]);
    // El destino llega codificado ("admin%2Fdashboard"): se decodifica antes de comparar.
    $correcto = $r['codigo'] === 302 && str_contains(rawurldecode($r['destino']), $panel);
    printf("  %-15s -> %-40s %s\n", $rol, $r['destino'], $correcto ? '[OK]' : '[FALLO]');
    if (!$correcto) {
        $fallos[] = "login con sesión de {$rol} no redirige a {$panel}";
    }
}

echo PHP_EOL . str_repeat('=', 78) . PHP_EOL;
printf("Comprobaciones fallidas:  %d\n", count($fallos));
echo count($fallos) === 0 ? "RESULTADO: TODAS LAS RUTAS RESPONDEN SEGUN SU ROL\n" : "RESULTADO: HAY FALLOS\n";
