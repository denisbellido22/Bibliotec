<?php
/**
 * BiblioTech — Gestión de préstamos y devoluciones (RF13).
 *
 * El mismo módulo sirve al panel administrativo y al del bibliotecario: el
 * prefijo de las rutas se resuelve según el rol autenticado, mientras que el
 * enrutador garantiza que un estudiante nunca pueda llegar aquí.
 */

declare(strict_types=1);

class PrestamoController extends Controller
{
    private Prestamo $prestamos;
    private Usuario $usuarios;
    private Libro $libros;
    private Reserva $reservas;

    public function __construct()
    {
        $this->prestamos = new Prestamo();
        $this->usuarios  = new Usuario();
        $this->libros    = new Libro();
        $this->reservas  = new Reserva();
    }

    /** Listado de préstamos con filtros. */
    public function index(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        // Mantiene el estado «vencido» al día antes de listar.
        $this->prestamos->actualizarVencidos();

        $estado  = (string) entrada('estado', '');
        $filtros = [
            'texto'  => (string) entrada('q', ''),
            'estado' => in_array($estado, array_merge([''], Prestamo::ESTADOS), true) ? $estado : '',
        ];

        $this->vista('prestamos.index', [
            'titulo'     => 'Préstamos',
            'conSidebar' => true,
            'prefijo'    => prefijo_rol(),
            'prestamos'  => $this->prestamos->buscar($filtros),
            'filtros'    => $filtros,
            'resumen'    => $this->prestamos->resumen(),
        ]);
    }

    /** Formulario y registro de un préstamo nuevo. */
    public function crear(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $datos   = [
            'id_usuario'                => entrada_int('id_usuario'),
            'id_libro'                  => entrada_int('id_libro'),
            'fecha_prestamo'            => date('Y-m-d'),
            'fecha_devolucion_esperada' => date('Y-m-d', strtotime('+' . (int) config('prestamos.dias_prestamo', 7) . ' days')),
        ];
        $errores = [];
        $reservaOrigen = null;

        if (es_post()) {
            $datos = [
                'id_usuario'                => entrada_int('id_usuario'),
                'id_libro'                  => entrada_int('id_libro'),
                'fecha_prestamo'            => (string) entrada('fecha_prestamo', date('Y-m-d')),
                'fecha_devolucion_esperada' => (string) entrada('fecha_devolucion_esperada', ''),
            ];

            $errores = $this->validar($datos);

            if ($errores === []) {
                // Si existe una reserva pendiente del título, se vincula al préstamo.
                $reservaOrigen = $this->reservas->pendienteDe($datos['id_usuario'], $datos['id_libro']);

                $resultado = $this->prestamos->registrar([
                    'id_usuario'                => $datos['id_usuario'],
                    'id_libro'                  => $datos['id_libro'],
                    'id_reserva'                => $reservaOrigen,
                    'fecha_prestamo'            => $datos['fecha_prestamo'],
                    'fecha_devolucion_esperada' => $datos['fecha_devolucion_esperada'],
                ]);

                if ($resultado['ok']) {
                    flash('exito', $resultado['mensaje']);
                    redirigir(prefijo_rol() . '/prestamos/ver', ['id' => (int) $resultado['id']]);
                }

                $errores['general'] = $resultado['mensaje'];
            }
        }

        $this->vista('prestamos.crear', [
            'titulo'       => 'Registrar préstamo',
            'conSidebar'   => true,
            'prefijo'      => prefijo_rol(),
            'datos'        => $datos,
            'errores'      => $errores,
            'usuarios'     => $this->usuarios->buscar(['estado' => 'activo']),
            'libros'       => $this->libros->buscar(['disponibilidad' => 'disponibles'], 1, 48)['datos'],
            'reserva'      => $reservaOrigen,
            'diasPrestamo' => (int) config('prestamos.dias_prestamo', 7),
        ]);
    }

    /** Ficha del préstamo con la acción de devolución. */
    public function ver(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $prestamo = $this->prestamos->obtener(entrada_int('id'));

        if ($prestamo === null) {
            flash('error', 'El préstamo solicitado no existe.');
            redirigir(prefijo_rol() . '/prestamos');
        }

        $this->vista('prestamos.ver', [
            'titulo'     => 'Préstamo #' . $prestamo['id'],
            'conSidebar' => true,
            'prefijo'    => prefijo_rol(),
            'prestamo'   => $prestamo,
        ]);
    }

    /** Registro de la devolución y actualización del stock. */
    public function devolver(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $id = entrada_int('id');

        if (!es_post()) {
            redirigir(prefijo_rol() . '/prestamos');
        }

        $resultado = $this->prestamos->registrarDevolucion($id);

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir(prefijo_rol() . '/prestamos');
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /**
     * @param array<string, mixed> $datos
     * @return array<string, string>
     */
    private function validar(array $datos): array
    {
        $errores = [];

        $usuario = $datos['id_usuario'] > 0 ? $this->usuarios->obtener((int) $datos['id_usuario']) : null;

        if ($usuario === null) {
            $errores['id_usuario'] = 'Selecciona un usuario válido.';
        } elseif ($usuario['estado'] !== 'activo') {
            $errores['id_usuario'] = 'El usuario está bloqueado y no puede recibir préstamos.';
        }

        $libro = $datos['id_libro'] > 0 ? $this->libros->obtener((int) $datos['id_libro']) : null;

        if ($libro === null) {
            $errores['id_libro'] = 'Selecciona un libro válido.';
        } elseif ((int) $libro['stock_disponible'] <= 0) {
            $errores['id_libro'] = 'El libro «' . $libro['titulo'] . '» no tiene ejemplares disponibles.';
        }

        if ($datos['fecha_prestamo'] === '' || strtotime($datos['fecha_prestamo']) === false) {
            $errores['fecha_prestamo'] = 'Indica una fecha de préstamo válida.';
        }

        if ($datos['fecha_devolucion_esperada'] === '' || strtotime($datos['fecha_devolucion_esperada']) === false) {
            $errores['fecha_devolucion_esperada'] = 'Indica una fecha de devolución válida.';
        } elseif (strtotime($datos['fecha_devolucion_esperada']) < strtotime($datos['fecha_prestamo'])) {
            $errores['fecha_devolucion_esperada'] = 'La devolución esperada no puede ser anterior al préstamo.';
        }

        // Se evita prestar un título vetado por una multa pendiente.
        if ($usuario !== null && (new Multa())->tienePendientes((int) $usuario['id'])) {
            $errores['general'] = 'El usuario tiene multas pendientes. Regulariza la situación antes de prestarle.';
        }

        return $errores;
    }
}
