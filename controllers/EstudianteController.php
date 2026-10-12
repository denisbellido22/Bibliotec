<?php
/**
 * BiblioTech — Panel del estudiante.
 *
 * Todas las consultas se filtran por el identificador del usuario autenticado:
 * un estudiante nunca puede ver información de otro (control de acceso por
 * registro).
 */

declare(strict_types=1);

class EstudianteController extends Controller
{
    private Prestamo $prestamos;
    private Reserva $reservas;
    private Multa $multas;

    public function __construct()
    {
        $this->prestamos = new Prestamo();
        $this->reservas  = new Reserva();
        $this->multas    = new Multa();
    }

    /** Resumen personal del estudiante. */
    public function panel(): void
    {
        $this->exigirRol('estudiante');

        $idUsuario = id_usuario_actual();

        $this->prestamos->actualizarVencidos();
        $this->reservas->actualizarExpiradas();

        $prestamos = $this->prestamos->buscar(['id_usuario' => $idUsuario]);
        $reservas  = $this->reservas->buscar(['id_usuario' => $idUsuario]);

        // Se muestran sólo los movimientos abiertos en el resumen.
        $abiertos = array_values(array_filter(
            $prestamos,
            static fn (array $p): bool => in_array($p['estado'], ['activo', 'vencido'], true)
        ));

        $this->vista('estudiante.panel', [
            'titulo'        => 'Mi cuenta',
            'conSidebar'    => true,
            'resumenPrestamos' => $this->prestamos->resumen($idUsuario),
            'resumenReservas'  => $this->reservas->resumen($idUsuario),
            'resumenMultas'    => $this->multas->resumen($idUsuario),
            'prestamosAbiertos' => array_slice($abiertos, 0, 5),
            'reservas'      => array_slice($reservas, 0, 5),
        ]);
    }

    /** Préstamos propios con sus fechas de devolución. */
    public function prestamos(): void
    {
        $this->exigirRol('estudiante');

        $this->prestamos->actualizarVencidos();

        $idUsuario = id_usuario_actual();

        $this->vista('estudiante.prestamos', [
            'titulo'     => 'Mis préstamos',
            'conSidebar' => true,
            'prestamos'  => $this->prestamos->buscar(['id_usuario' => $idUsuario]),
            'resumen'    => $this->prestamos->resumen($idUsuario),
        ]);
    }
}
