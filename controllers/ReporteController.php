<?php
/**
 * BiblioTech — Reportes y estadísticas (RF02, RF13).
 *
 * Todos los cuadros se calculan con consultas de agregación sobre la base de
 * datos real. El período es configurable por el usuario.
 */

declare(strict_types=1);

class ReporteController extends Controller
{
    private Estadistica $estadisticas;
    private Libro $libros;
    private Usuario $usuarios;
    private Multa $multas;
    private Prestamo $prestamos;
    private Reserva $reservas;

    public function __construct()
    {
        $this->estadisticas = new Estadistica();
        $this->libros       = new Libro();
        $this->usuarios     = new Usuario();
        $this->multas       = new Multa();
        $this->prestamos    = new Prestamo();
        $this->reservas     = new Reserva();
    }

    /** Reporte general del sistema. */
    public function index(): void
    {
        $this->exigirRol('administrador');

        // Meses de histórico que el administrador quiere revisar.
        $meses = entrada_int('meses', 6);
        $meses = in_array($meses, [3, 6, 12], true) ? $meses : 6;

        $this->prestamos->actualizarVencidos();
        $this->reservas->actualizarExpiradas();

        $this->vista('admin.reportes.index', [
            'titulo'          => 'Reportes',
            'conSidebar'      => true,
            'meses'           => $meses,
            'indicadores'     => $this->estadisticas->panelAdministrativo(),
            'catalogo'        => $this->libros->resumenStock(),
            'usuarios'        => $this->usuarios->resumen(),
            'multas'          => $this->multas->resumen(),
            'prestamos'       => $this->prestamos->resumen(),
            'reservas'        => $this->reservas->resumen(),
            'seriePrestamos'  => $this->estadisticas->prestamosUltimosMeses($meses),
            'serieCobros'     => $this->estadisticas->cobrosUltimosMeses($meses),
            'porCategoria'    => $this->estadisticas->prestamosPorCategoria(),
            'masPrestados'    => $this->estadisticas->librosMasPrestados(8),
            'catalogoCategoria' => $this->estadisticas->catalogoPorCategoria(),
            'estadoCatalogo'  => $this->estadisticas->estadoCatalogo(),
        ]);
    }
}
