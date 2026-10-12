<?php
/**
 * BiblioTech — Gestión de multas.
 *
 * El personal autorizado registra pagos y condonaciones; el estudiante sólo
 * consulta sus propias multas. No se simulan pasarelas de pago: el cobro se
 * registra como cambio de estado con fecha.
 */

declare(strict_types=1);

class MultaController extends Controller
{
    private Multa $multas;

    public function __construct()
    {
        $this->multas = new Multa();
    }

    /** Listado de multas para el personal. */
    public function index(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $estado  = (string) entrada('estado', '');
        $filtros = [
            'texto'  => (string) entrada('q', ''),
            'estado' => in_array($estado, array_merge([''], Multa::ESTADOS), true) ? $estado : '',
        ];

        $this->vista('multas.index', [
            'titulo'     => 'Multas',
            'conSidebar' => true,
            'prefijo'    => prefijo_rol(),
            'multas'     => $this->multas->buscar($filtros),
            'filtros'    => $filtros,
            'resumen'    => $this->multas->resumen(),
        ]);
    }

    /** Registra el pago de una multa pendiente. */
    public function pagar(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $resultado = $this->multas->registrarPago(entrada_int('id'));

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir(prefijo_rol() . '/multas');
    }

    /** Condona una multa pendiente sin registrar cobro. */
    public function condonar(): void
    {
        $this->exigirRol('administrador', 'bibliotecario');

        $resultado = $this->multas->condonar(entrada_int('id'));

        flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
        redirigir(prefijo_rol() . '/multas');
    }

    /* ------------------------------------------------------------------
     * Área del estudiante
     * ------------------------------------------------------------------ */

    /** Multas propias: sólo las del usuario autenticado (control por registro). */
    public function misMultas(): void
    {
        $this->exigirRol('estudiante');

        $idUsuario = id_usuario_actual();

        $this->vista('estudiante.multas', [
            'titulo'     => 'Mis multas',
            'conSidebar' => true,
            'multas'     => $this->multas->buscar(['id_usuario' => $idUsuario]),
            'resumen'    => $this->multas->resumen($idUsuario),
        ]);
    }
}
