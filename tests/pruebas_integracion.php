<?php
/**
 * BiblioTech — Pruebas de integración de los escenarios de aceptación.
 *
 * Recorre, contra el sistema en ejecución por HTTP, los trece escenarios de
 * la sección 10 de las instrucciones, incluyendo la separación de permisos
 * entre los tres roles.
 *
 * Uso:
 *   php tests/pruebas_integracion.php                       (usa localhost/BiblioTech)
 *   php tests/pruebas_integracion.php http://localhost:8099 (otra dirección)
 *
 * Los datos de prueba que crea se eliminan al finalizar: la base de datos
 * `Bibliotec` queda como estaba antes de ejecutar las pruebas.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/database.php';

$base = $argv[1] ?? 'http://localhost/BiblioTech/index.php';

/* ==========================================================
   Cliente HTTP mínimo con manejo de cookies y redirecciones
   ========================================================== */

final class Navegador
{
    private string $jar;

    public function __construct(private string $base, string $nombre)
    {
        $this->jar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bibliotech_' . $nombre . '.cookie';

        if (is_file($this->jar)) {
            @unlink($this->jar);
        }
    }

    public function __destruct()
    {
        if (is_file($this->jar)) {
            @unlink($this->jar);
        }
    }

    /**
     * @param array<string, mixed> $parametros
     * @return array{codigo: int, cuerpo: string, ubicacion: string|null}
     */
    public function peticion(array $parametros = [], ?array $post = null, ?array $archivo = null): array
    {
        $url = $this->base;

        if ($parametros !== []) {
            $url .= '?' . http_build_query($parametros);
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 20,
        ]);

        if ($post !== null) {
            $campos = $post;

            if ($archivo !== null) {
                $campos[$archivo['campo']] = new CURLFile(
                    $archivo['ruta'],
                    $archivo['tipo'],
                    $archivo['nombre']
                );
            }

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $campos);
        }

        $cuerpo = (string) curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $ubicacion = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        curl_close($curl);

        return [
            'codigo'    => $codigo,
            'cuerpo'    => $cuerpo,
            'ubicacion' => is_string($ubicacion) && $ubicacion !== '' ? $ubicacion : null,
        ];
    }

    /** Token CSRF presente en una página. */
    public function token(string $ruta, array $parametros = []): string
    {
        $respuesta = $this->peticion(['ruta' => $ruta] + $parametros);

        if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $respuesta['cuerpo'], $coincidencia) === 1) {
            return $coincidencia[1];
        }

        return '';
    }

    /** Inicia sesión y devuelve el destino al que redirige. */
    public function acceder(string $email, string $password): array
    {
        $token = $this->token('login');

        return $this->peticion(
            ['ruta' => 'login'],
            ['csrf_token' => $token, 'email' => $email, 'password' => $password]
        );
    }
}

/* ==========================================================
   Utilidades de prueba
   ========================================================== */

$exitos = 0;
$fallos = 0;
$escenario = '';

function escenario(string $titulo): void
{
    global $escenario;
    $escenario = $titulo;
    echo PHP_EOL . '== ' . $titulo . ' ==' . PHP_EOL;
}

function comprobar(string $descripcion, bool $condicion, string $detalle = ''): void
{
    global $exitos, $fallos;

    if ($condicion) {
        $exitos++;
        echo "  [OK]    {$descripcion}" . PHP_EOL;

        return;
    }

    $fallos++;
    echo "  [FALLA] {$descripcion}";
    echo $detalle !== '' ? ' -> ' . $detalle : '';
    echo PHP_EOL;
}

function contiene(string $cuerpo, string $texto): bool
{
    return $texto !== '' && mb_stripos($cuerpo, $texto) !== false;
}

/** Importe de una columna concreta de una tabla. */
function valor(string $sql, array $parametros = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    $dato = $stmt->fetchColumn();

    return $dato === false ? null : $dato;
}

function marcaDeTiempo(): string
{
    return date('YmdHis');
}

/* ==========================================================
   Preparación: usuarios de demostración del sistema real
   ========================================================== */

echo 'BiblioTech — Pruebas de integración' . PHP_EOL;
echo 'Destino: ' . $base . PHP_EOL;

$admin   = new Navegador($base, 'admin');
$biblio  = new Navegador($base, 'biblio');
$alumno  = new Navegador($base, 'alumno');
$alumno2 = new Navegador($base, 'alumno2');

$sufijo = marcaDeTiempo();

$idUsuarioEstudiante = (int) valor('SELECT id FROM usuarios WHERE email = :e', [':e' => 'carlos@bibliotech.test']);
$idLibroDisponible   = (int) valor('SELECT id FROM libros WHERE stock_disponible > 0 ORDER BY id LIMIT 1');
$stockInicial        = (int) valor('SELECT stock_disponible FROM libros WHERE id = :id', [':id' => $idLibroDisponible]);

// Registros creados durante las pruebas, para limpiarlos al final.
$creados = ['libros' => [], 'categorias' => [], 'usuarios' => [], 'prestamos' => [], 'reservas' => []];

/* ----------------------------------------------------------
   Escenario 1: el administrador iniciar sesión y gestiona libros
   ---------------------------------------------------------- */
escenario('1. El administrador inicia sesión y gestiona libros');

$acceso = $admin->acceder('admin@bibliotech.test', 'password');
comprobar('El administrador inicia sesión (redirección al panel)', $acceso['codigo'] === 302, 'HTTP ' . $acceso['codigo']);
comprobar('Entra al panel administrativo', str_contains((string) $acceso['ubicacion'], 'admin%2Fdashboard'));

$respuesta = $admin->peticion(['ruta' => 'admin/libros']);
comprobar('Consulta el listado de libros', $respuesta['codigo'] === 200 && contiene($respuesta['cuerpo'], 'Libros'));

// Alta de un libro con portada subida desde el archivo.
$imagen = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qa_portada.png';
file_put_contents($imagen, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
));

$token = $admin->token('admin/libros/crear');
$alta = $admin->peticion(['ruta' => 'admin/libros/crear'], [
    'csrf_token'      => $token,
    'titulo'          => 'Libro de Pruebas ' . $sufijo,
    'autor'           => 'Autor de Pruebas',
    // ISBN de 13 dígitos, como exige la validación del formulario.
    'isbn'            => '978' . substr($sufijo, -10),
    'id_categoria'    => (string) valor('SELECT id FROM categorias ORDER BY id LIMIT 1'),
    'editorial'       => 'Editorial QA',
    'anio'            => '2026',
    'stock_total'     => '3',
    'stock_disponible' => '3',
    'sinopsis'        => 'Registro temporal creado por las pruebas de integración.',
], [
    'campo'  => 'portada',
    'ruta'   => $imagen,
    'tipo'   => 'image/png',
    'nombre' => 'portada_qa.png',
]);

$idLibroCreado = (int) valor('SELECT id FROM libros WHERE titulo = :t', [':t' => 'Libro de Pruebas ' . $sufijo]);
$creados['libros'][] = $idLibroCreado;

comprobar('Registra un libro nuevo', $alta['codigo'] === 302 && $idLibroCreado > 0);
comprobar(
    'La portada se guarda en uploads/portadas',
    (bool) valor('SELECT portada_url FROM libros WHERE id = :id', [':id' => $idLibroCreado])
        && is_file(BASE_PATH . '/uploads/portadas/' . valor('SELECT portada_url FROM libros WHERE id = :id', [':id' => $idLibroCreado]))
);

// Edición del libro recién creado.
$token = $admin->token('admin/libros/editar', ['id' => $idLibroCreado]);
$admin->peticion(['ruta' => 'admin/libros/editar', 'id' => $idLibroCreado], [
    'csrf_token'       => $token,
    'titulo'           => 'Libro de Pruebas Editado ' . $sufijo,
    'autor'            => 'Autor Editado',
    'isbn'             => '978' . substr($sufijo, -10),
    'editorial'        => 'Editorial QA',
    'anio'             => '2025',
    'stock_total'      => '3',
    'stock_disponible' => '3',
]);

comprobar(
    'Edita el libro registrado',
    contiene((string) valor('SELECT titulo FROM libros WHERE id = :id', [':id' => $idLibroCreado]), 'Editado')
);

// Validación de campos incorrectos.
$token = $admin->token('admin/libros/crear');
$invalido = $admin->peticion(['ruta' => 'admin/libros/crear'], [
    'csrf_token' => $token,
    'titulo'     => '',
    'autor'      => 'X',
    'stock_total' => '2',
    'stock_disponible' => '9',
]);

comprobar('Rechaza el título vacío con un mensaje claro', contiene($invalido['cuerpo'], 'El título es obligatorio.'));
comprobar(
    'Rechaza un stock disponible mayor al total',
    contiene($invalido['cuerpo'], 'no pueden superar el stock total')
);

/* ----------------------------------------------------------
   Escenario 2: el administrador crea categorías y gestiona usuarios
   ---------------------------------------------------------- */
escenario('2. El administrador crea categorías y gestiona usuarios');

$token = $admin->token('admin/categorias/crear');
$cat = $admin->peticion(['ruta' => 'admin/categorias/crear'], [
    'csrf_token'  => $token,
    'nombre'      => 'Categoría QA ' . $sufijo,
    'descripcion' => 'Categoría temporal de verificación.',
]);

$idCategoria = (int) valor('SELECT id FROM categorias WHERE nombre = :n', [':n' => 'Categoría QA ' . $sufijo]);
$creados['categorias'][] = $idCategoria;

comprobar('Crea una categoría nueva', $cat['codigo'] === 302 && $idCategoria > 0);

$token = $admin->token('admin/categorias/crear');
$duplicada = $admin->peticion(['ruta' => 'admin/categorias/crear'], [
    'csrf_token' => $token,
    'nombre'     => 'Categoría QA ' . $sufijo,
]);
comprobar('Rechaza nombres de categoría duplicados', contiene($duplicada['cuerpo'], 'Ya existe una categoría con ese nombre'));

$token = $admin->token('admin/usuarios/crear');
$altaUsuario = $admin->peticion(['ruta' => 'admin/usuarios/crear'], [
    'csrf_token' => $token,
    'nombre'     => 'Usuario QA ' . $sufijo,
    'email'      => 'qa' . $sufijo . '@bibliotech.test',
    'rol'        => 'estudiante',
    'estado'     => 'activo',
    'password'   => 'ClaveSegura123',
]);

$idUsuarioCreado = (int) valor('SELECT id FROM usuarios WHERE email = :e', [':e' => 'qa' . $sufijo . '@bibliotech.test']);
$creados['usuarios'][] = $idUsuarioCreado;

comprobar('Registra un usuario nuevo', $altaUsuario['codigo'] === 302 && $idUsuarioCreado > 0);
comprobar(
    'La contraseña se almacena cifrada con bcrypt',
    str_starts_with((string) valor('SELECT password_hash FROM usuarios WHERE id = :id', [':id' => $idUsuarioCreado]), '$2y$')
);

$busqueda = $admin->peticion(['ruta' => 'admin/usuarios', 'q' => 'qa' . $sufijo . '@bibliotech.test']);
comprobar('Busca usuarios por correo (RF10)', contiene($busqueda['cuerpo'], '1 resultado'));

$filtrado = $admin->peticion(['ruta' => 'admin/usuarios', 'rol' => 'administrador']);
comprobar('Filtra usuarios por rol (RF11)', contiene($filtrado['cuerpo'], 'resultado'));

// Bloqueo del usuario y comprobación de que no puede entrar.
$token = $admin->token('admin/usuarios');
$admin->peticion(['ruta' => 'admin/usuarios/estado', 'id' => $idUsuarioCreado], ['csrf_token' => $token]);

$bloqueado = new Navegador($base, 'bloqueado');
$intento = $bloqueado->acceder('qa' . $sufijo . '@bibliotech.test', 'ClaveSegura123');
comprobar('Un usuario bloqueado no puede iniciar sesión', $intento['codigo'] === 200 && contiene($intento['cuerpo'], 'bloqueada'));

// El sistema no permite quedarse sin administradores.
$idAdmin = (int) valor('SELECT id FROM usuarios WHERE rol = "administrador" AND estado = "activo" ORDER BY id LIMIT 1');
$token = $admin->token('admin/usuarios/editar', ['id' => $idAdmin]);
$sinAdmin = $admin->peticion(['ruta' => 'admin/usuarios/editar', 'id' => $idAdmin], [
    'csrf_token' => $token,
    'nombre'     => 'Administrador BiblioTech',
    'email'      => 'admin@bibliotech.test',
    'rol'        => 'estudiante',
    'estado'     => 'activo',
]);
comprobar(
    'Impide dejar el sistema sin administradores activos',
    contiene($sinAdmin['cuerpo'], 'al menos un administrador activo')
);

/* ----------------------------------------------------------
   Escenario 3-5: préstamo válido, el stock baja y la devolución lo sube
   ---------------------------------------------------------- */
escenario('3-5. Préstamo válido, descuento y reposición del stock');

$entrada = $biblio->acceder('biblio@bibliotech.test', 'password');
comprobar('El bibliotecario inicia sesión', $entrada['codigo'] === 302, 'HTTP ' . $entrada['codigo']);

$token = $biblio->token('bibliotecario/prestamos/crear');
$prestamo = $biblio->peticion(['ruta' => 'bibliotecario/prestamos/crear'], [
    'csrf_token'                => $token,
    'id_usuario'                => $idUsuarioEstudiante,
    'id_libro'                  => $idLibroDisponible,
    'fecha_prestamo'            => date('Y-m-d'),
    'fecha_devolucion_esperada' => date('Y-m-d', strtotime('+7 days')),
]);

$idPrestamo = (int) valor(
    'SELECT id FROM prestamos WHERE id_usuario = :u AND id_libro = :l ORDER BY id DESC LIMIT 1',
    [':u' => $idUsuarioEstudiante, ':l' => $idLibroDisponible]
);
$creados['prestamos'][] = $idPrestamo;

comprobar('El bibliotecario registra un préstamo válido', $prestamo['codigo'] === 302 && $idPrestamo > 0);

$stockTrasPrestamo = (int) valor('SELECT stock_disponible FROM libros WHERE id = :id', [':id' => $idLibroDisponible]);
comprobar(
    'El stock disponible disminuye al prestar',
    $stockTrasPrestamo === $stockInicial - 1,
    "antes {$stockInicial}, después {$stockTrasPrestamo}"
);

$token = $biblio->token('bibliotecario/prestamos');
$devolucion = $biblio->peticion(['ruta' => 'bibliotecario/prestamos/devolver', 'id' => $idPrestamo], [
    'csrf_token' => $token,
]);
$stockTrasDevolver = (int) valor('SELECT stock_disponible FROM libros WHERE id = :id', [':id' => $idLibroDisponible]);

comprobar('El bibliotecario registra la devolución', $devolucion['codigo'] === 302);
comprobar(
    'El stock disponible aumenta al devolver',
    $stockTrasDevolver === $stockInicial,
    "esperado {$stockInicial}, obtenido {$stockTrasDevolver}"
);
comprobar(
    'El préstamo queda marcado como devuelto',
    valor('SELECT estado FROM prestamos WHERE id = :id', [':id' => $idPrestamo]) === 'devuelto'
);

/* ----------------------------------------------------------
   Escenario 6: no se puede prestar un libro sin disponibilidad
   ---------------------------------------------------------- */
escenario('6. No se puede prestar un libro sin ejemplares disponibles');
db()->prepare(
    'INSERT INTO libros (titulo, autor, isbn, stock_total, stock_disponible)
     VALUES (:t, :a, :i, 1, 0)'
)->execute([':t' => 'Libro Agotado QA ' . $sufijo, ':a' => 'QA', ':i' => '979' . substr($sufijo, -10)]);

$idLibroAgotado = (int) valor('SELECT id FROM libros WHERE titulo = :t', [':t' => 'Libro Agotado QA ' . $sufijo]);
$creados['libros'][] = $idLibroAgotado;

$token = $biblio->token('bibliotecario/prestamos/crear');
$sinStock = $biblio->peticion(['ruta' => 'bibliotecario/prestamos/crear'], [
    'csrf_token'                => $token,
    'id_usuario'                => $idUsuarioEstudiante,
    'id_libro'                  => $idLibroAgotado,
    'fecha_prestamo'            => date('Y-m-d'),
    'fecha_devolucion_esperada' => date('Y-m-d', strtotime('+7 days')),
]);

comprobar(
    'El sistema rechaza prestar un título agotado',
    contiene($sinStock['cuerpo'], 'no tiene ejemplares disponibles')
);
comprobar(
    'No se crea ningún préstamo sobre el libro agotado',
    (int) valor('SELECT COUNT(*) FROM prestamos WHERE id_libro = :id', [':id' => $idLibroAgotado]) === 0
);

// Préstamo duplicado del mismo título: primero se abre uno y luego se repite.
$token = $biblio->token('bibliotecario/prestamos/crear');
$biblio->peticion(['ruta' => 'bibliotecario/prestamos/crear'], [
    'csrf_token'                => $token,
    'id_usuario'                => $idUsuarioEstudiante,
    'id_libro'                  => $idLibroDisponible,
    'fecha_prestamo'            => date('Y-m-d'),
    'fecha_devolucion_esperada' => date('Y-m-d', strtotime('+7 days')),
]);

$token = $biblio->token('bibliotecario/prestamos/crear');
$duplicado = $biblio->peticion(['ruta' => 'bibliotecario/prestamos/crear'], [
    'csrf_token'                => $token,
    'id_usuario'                => $idUsuarioEstudiante,
    'id_libro'                  => $idLibroDisponible,
    'fecha_prestamo'            => date('Y-m-d'),
    'fecha_devolucion_esperada' => date('Y-m-d', strtotime('+7 days')),
]);

comprobar('Evita préstamos duplicados del mismo ejemplar', contiene($duplicado['cuerpo'], 'ya tiene un ejemplar de este libro'));

// Se cierra el préstamo abierto en esta comprobación.
$idPrestamoAbierto = (int) valor(
    'SELECT id FROM prestamos WHERE id_usuario = :u AND id_libro = :l AND estado IN ("activo","vencido") ORDER BY id DESC LIMIT 1',
    [':u' => $idUsuarioEstudiante, ':l' => $idLibroDisponible]
);
$creados['prestamos'][] = $idPrestamoAbierto;
$token = $biblio->token('bibliotecario/prestamos');
$biblio->peticion(['ruta' => 'bibliotecario/prestamos/devolver', 'id' => $idPrestamoAbierto], ['csrf_token' => $token]);

/* ----------------------------------------------------------
   Escenario 7: el estudiante busca y filtra libros
   ---------------------------------------------------------- */
escenario('7. El estudiante busca y filtra libros por título, autor, ISBN y categoría');

$entrada = $alumno->acceder('carlos@bibliotech.test', 'password');
comprobar('El estudiante inicia sesión', $entrada['codigo'] === 302, 'HTTP ' . $entrada['codigo']);

foreach ([
    'Título'  => ['q' => 'Clean'],
    'Autor'   => ['q' => 'Kurose'],
    'ISBN'    => ['q' => '9780132350884'],
] as $etiqueta => $consulta) {
    $respuesta = $alumno->peticion(['ruta' => 'catalogo'] + $consulta);
    comprobar("Busca por {$etiqueta}", $respuesta['codigo'] === 200 && contiene($respuesta['cuerpo'], 'encontrado'));
}

$idCategoriaPrueba = (int) valor('SELECT id FROM categorias ORDER BY id LIMIT 1');
$porCategoria = $alumno->peticion(['ruta' => 'catalogo', 'categoria' => $idCategoriaPrueba]);
comprobar('Filtra el catálogo por categoría (RF06)', $porCategoria['codigo'] === 200 && contiene($porCategoria['cuerpo'], 'encontrado'));

$agotados = $alumno->peticion(['ruta' => 'catalogo', 'disponibilidad' => 'agotados']);
comprobar('Filtra por disponibilidad (RF07)', $agotados['codigo'] === 200);

/* ----------------------------------------------------------
   Escenario 8: el estudiante sólo ve su propia información
   ---------------------------------------------------------- */
escenario('8. El estudiante consulta sus reservas, préstamos y multas sin ver los de otros');

$otroEstudiante = (int) valor('SELECT id FROM usuarios WHERE rol = "estudiante" AND id <> :id ORDER BY id LIMIT 1', [':id' => $idUsuarioEstudiante]);

db()->prepare(
    'INSERT INTO reservas (id_usuario, id_libro, fecha_reserva, fecha_expiracion, estado)
     VALUES (:u, :l, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), "pendiente")'
)->execute([':u' => $otroEstudiante, ':l' => $idLibroDisponible]);
$idReservaAjena = (int) db()->lastInsertId();
$creados['reservas'][] = $idReservaAjena;

$misReservas = $alumno->peticion(['ruta' => 'estudiante/reservas']);
comprobar('Consulta sus reservas', $misReservas['codigo'] === 200);
$nombreAjeno = (string) valor('SELECT nombre FROM usuarios WHERE id = :id', [':id' => $otroEstudiante]);
comprobar(
    'No ve la reserva de otro estudiante',
    !contiene($misReservas['cuerpo'], $nombreAjeno),
    'aparece ' . $nombreAjeno
);

$misPrestamos = $alumno->peticion(['ruta' => 'estudiante/prestamos']);
$otrosNombres = 0;
foreach (db()->query('SELECT nombre FROM usuarios WHERE id <> ' . $idUsuarioEstudiante) as $fila) {
    if (contiene($misPrestamos['cuerpo'], (string) $fila['nombre'])) {
        $otrosNombres++;
    }
}
comprobar('En «Mis préstamos» no aparece ningún otro usuario', $otrosNombres === 0);

$token = $alumno->token('estudiante/reservas');
$intentoAjeno = $alumno->peticion(['ruta' => 'estudiante/reservas/cancelar', 'id' => $idReservaAjena], ['csrf_token' => $token]);
comprobar(
    'No puede cancelar una reserva ajena',
    valor('SELECT estado FROM reservas WHERE id = :id', [':id' => $idReservaAjena]) === 'pendiente'
);

$misMultas = $alumno->peticion(['ruta' => 'estudiante/multas']);
comprobar('Consulta sus multas y estados de pago', $misMultas['codigo'] === 200);

/* ----------------------------------------------------------
   Escenario 9: el estudiante no entra al panel administrativo
   ---------------------------------------------------------- */
escenario('9. Un estudiante no puede entrar al panel administrativo escribiendo la URL');

foreach ([
    'admin/dashboard',
    'admin/libros',
    'admin/usuarios',
    'admin/roles',
    'admin/prestamos',
    'admin/reportes',
] as $ruta) {
    $respuesta = $alumno->peticion(['ruta' => $ruta]);
    comprobar("Acceso denegado a {$ruta}", $respuesta['codigo'] === 403, 'HTTP ' . $respuesta['codigo']);
}

comprobar(
    'Tampoco puede acceder al panel del bibliotecario',
    $alumno->peticion(['ruta' => 'bibliotecario/panel'])['codigo'] === 403
);

/* ----------------------------------------------------------
   Escenario 10: el bibliotecario no accede a funciones del administrador
   ---------------------------------------------------------- */
escenario('10. Un bibliotecario no accede a funciones exclusivas del administrador');

foreach ([
    'admin/usuarios',
    'admin/roles',
    'admin/libros',
    'admin/categorias',
] as $ruta) {
    $respuesta = $biblio->peticion(['ruta' => $ruta]);
    comprobar("Acceso denegado a {$ruta}", $respuesta['codigo'] === 403, 'HTTP ' . $respuesta['codigo']);
}

comprobar(
    'El bibliotecario sí puede registrar préstamos',
    $biblio->peticion(['ruta' => 'bibliotecario/prestamos/crear'])['codigo'] === 200
);

/* ----------------------------------------------------------
   Escenario 11: validación de formularios y mensajes comprensibles
   ---------------------------------------------------------- */
escenario('11. Los formularios validan datos incorrectos con mensajes comprensibles');

$pantalla = new Navegador($base, 'pantalla');
$respuesta = $pantalla->peticion(['ruta' => 'login']);

comprobar('El formulario de acceso muestra ayuda de credenciales', contiene($respuesta['cuerpo'], 'Iniciar sesión'));

$incorrecto = $pantalla->acceder('admin@bibliotech.test', 'clave-incorrecta');
comprobar(
    'Credenciales incorrectas muestran un mensaje claro',
    contiene($incorrecto['cuerpo'], 'El correo o la contraseña son incorrectos')
);

$token = $pantalla->token('login');
$malFormato = $pantalla->peticion(['ruta' => 'login'], ['csrf_token' => $token, 'email' => 'correo-invalido', 'password' => 'x']);
comprobar('Valida el formato del correo', contiene($malFormato['cuerpo'], 'no tiene un formato válido'));

$sinCsrf = $admin->peticion(['ruta' => 'admin/usuarios/estado', 'id' => $idUsuarioCreado], ['csrf_token' => 'invalido']);
comprobar(
    'Los formularios sin token CSRF válido se rechazan',
    $sinCsrf['codigo'] === 403 && contiene($sinCsrf['cuerpo'], 'Formulario expirado'),
    'HTTP ' . $sinCsrf['codigo']
);

/* ----------------------------------------------------------
   Escenario 12: los datos persisten
   ---------------------------------------------------------- */
escenario('12. Los datos permanecen guardados entre sesiones');

$admin->peticion(['ruta' => 'admin/usuarios']);
$nuevaSesion = new Navegador($base, 'nueva');
$nuevaSesion->acceder('biblio@bibliotech.test', 'password');
$listado = $nuevaSesion->peticion(['ruta' => 'bibliotecario/libros', 'q' => 'Libro de Pruebas Editado ' . $sufijo]);

comprobar(
    'El libro editado sigue disponible en otra sesión',
    contiene($listado['cuerpo'], 'Libro de Pruebas Editado ' . $sufijo)
);

$usuarioPersistido = valor('SELECT nombre FROM usuarios WHERE id = :id', [':id' => $idUsuarioCreado]);
comprobar('El usuario creado sigue registrado', is_string($usuarioPersistido) && $usuarioPersistido !== '');

/* ----------------------------------------------------------
   Escenario 13: diseño adaptable
   ---------------------------------------------------------- */
escenario('13. La interfaz es adaptable a computadora y móvil');

$hoja = $admin->peticion(['ruta' => 'catalogo']);
preg_match('#href="([^"]*assets/css/app\.css)"#', $hoja['cuerpo'], $coincidencia);
$rutaCss = $coincidencia[1] ?? '';

/* La ruta es relativa al host: se reconstruye la dirección absoluta. */
$partes = parse_url($base);
$raiz = ($partes['scheme'] ?? 'http') . '://' . ($partes['host'] ?? 'localhost')
    . (isset($partes['port']) ? ':' . $partes['port'] : '');
$urlCss = $rutaCss !== '' ? $raiz . $rutaCss : '';

$css = '';
if ($urlCss !== '') {
    $curl = curl_init($urlCss);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $css = (string) curl_exec($curl);
    curl_close($curl);
}

comprobar('La hoja de estilos se carga', $css !== '');
comprobar('Incluye reglas para pantallas pequeñas', substr_count($css, '@media') >= 4, substr_count($css, '@media') . ' bloques');
comprobar('Declara el viewport para móviles', contiene($hoja['cuerpo'], 'name="viewport"'));

/* ==========================================================
   Limpieza: se elimina sólo lo creado por estas pruebas
   ========================================================== */
echo PHP_EOL . '== Limpieza de datos de prueba ==' . PHP_EOL;

foreach ($creados['prestamos'] as $id) {
    db()->prepare('DELETE FROM prestamos WHERE id = :id')->execute([':id' => $id]);
}

foreach ($creados['reservas'] as $id) {
    db()->prepare('DELETE FROM reservas WHERE id = :id')->execute([':id' => $id]);
}

foreach ($creados['libros'] as $id) {
    $portada = valor('SELECT portada_url FROM libros WHERE id = :id', [':id' => $id]);

    if (is_string($portada) && $portada !== '' && preg_match('#^https?://#', $portada) !== 1) {
        $archivo = BASE_PATH . '/uploads/portadas/' . basename($portada);

        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }

    db()->prepare('DELETE FROM libros WHERE id = :id')->execute([':id' => $id]);
}

foreach ($creados['usuarios'] as $id) {
    db()->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id' => $id]);
}

foreach ($creados['categorias'] as $id) {
    db()->prepare('DELETE FROM categorias WHERE id = :id')->execute([':id' => $id]);
}

if (is_file($imagen)) {
    @unlink($imagen);
}

// El stock debe quedar como al inicio.
$stockFinal = (int) valor('SELECT stock_disponible FROM libros WHERE id = :id', [':id' => $idLibroDisponible]);
comprobar(
    'El stock quedó restaurado al valor inicial',
    $stockFinal === $stockInicial,
    "esperado {$stockInicial}, obtenido {$stockFinal}"
);

echo '  Datos de prueba eliminados.' . PHP_EOL;

/* ==========================================================
   Resultado
   ========================================================== */
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
printf('Comprobaciones superadas: %d%s', $exitos, PHP_EOL);
printf('Comprobaciones fallidas:  %d%s', $fallos, PHP_EOL);
echo str_repeat('=', 60) . PHP_EOL;

if ($fallos === 0) {
    echo 'RESULTADO: TODOS LOS ESCENARIOS PASARON' . PHP_EOL;
    exit(0);
}

echo 'RESULTADO: HAY COMPROBACIONES FALLIDAS' . PHP_EOL;
exit(1);
