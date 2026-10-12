<?php
/**
 * BiblioTech — Gestión de reservas.
 *
 * El personal supervisa y cancela reservas; el estudiante sólo puede crear y
 * cancelar las suyas. El control de acceso por registro se aplica pasando el
 * identificador del usuario autenticado al modelo.
 */

declare(strict_types=1);

class ReservaController extends Controller
{
    private Reserva $reservas;
    private Libro $libros;

    public function __construct()
    {
        $this->reservas = new Reserva();
        $this->libros    = new Libro();
    }

    /** Listado para el personal (RF: supervisión de reservas). */
    public function index(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $this->reservas->actualizarExpiradas();

        $estado  = (string) entrada('estado', '');
        $filtros = [
            'texto'  => (string) entrada('q', ''),
            'estado' => in_array($estado, array_merge([''], Reserva::ESTADOS), true) ? $estado : '',
        ];

        $this->vista('reservas.index', [
            'titulo'     => 'Reservas',
            'conSidebar' => true,
            'prefijo'    => prefijo_rol(),
            'reservas'   => $this->reservas->buscar($filtros),
            'filtros'    => $filtros,
            'resumen'    => $this->reservas->resumen(),
        ]);
    }

    /** El personal confirma la entrega de un ejemplar reservado. */
    public function confirmar(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $resultado = $this->reservas->confirmar(entrada_int('id'));

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir(prefijo_rol() . '/reservas');
    }

    /** El personal cancela una reserva concreta. */
    public function cancelar(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $resultado = $this->reservas->cancelar(entrada_int('id'));

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir(prefijo_rol() . '/reservas');
    }

    /* ------------------------------------------------------------------
     * Área del estudiante
     * ------------------------------------------------------------------ */

    /** Reservas propias del estudiante autenticado. */
    public function misReservas(): void
    {
        $this->exigirRol('estudiante');

        $this->reservas->actualizarExpiradas();

        $idUsuario = id_usuario_actual();

        $this->vista('estudiante.reservas', [
            'titulo'     => 'Mis reservas',
            'conSidebar' => true,
            'reservas'   => $this->reservas->buscar(['id_usuario' => $idUsuario]),
            'resumen'    => $this->reservas->resumen($idUsuario),
            'mensaje'    => null,
        ]);
    }

    /** Registra una reserva solicitada por el estudiante. */
    public function reservar(): void
    {
        $this->exigirRol('estudiante');

        if (!es_post()) {
            redirigir('catalogo');
        }

        $idUsuario = id_usuario_actual();
        $idLibro   = entrada_int('id_libro');

        $resultado = $this->reservas->registrar($idUsuario, $idLibro);

        if ($resultado['ok']) {
            flash('exito', $resultado['mensaje']);
            redirigir('estudiante/reservas');
        }

        flash('error', $resultado['mensaje']);

        // Si el error vino del detalle del libro, se vuelve a esa ficha.
        if ($idLibro > 0 && entrada('origen') === 'ficha') {
            redirigir('catalogo/ver', ['id' => $idLibro]);
        }

        redirigir('catalogo');
    }

    /** El estudiante cancela una reserva propia. */
    public function cancelarPropia(): void
    {
        $this->exigirRol('estudiante');

        $resultado = $this->reservas->cancelar(entrada_int('id'), id_usuario_actual());

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir('estudiante/reservas');
    }
}
