<?php
/**
 * BiblioTech — Panel del administrador (RF02).
 *
 * El dashboard se alimenta de la base de datos real: no hay cifras
 * estáticas ni tarjetas decorativas.
 */

declare(strict_types=1);

class AdminController extends Controller
{
    private Estadistica $estadisticas;
    private Libro $libros;
    private Usuario $usuarios;
    private Categoria $categorias;

    public function __construct()
    {
        $this->estadisticas = new Estadistica();
        $this->libros       = new Libro();
        $this->usuarios     = new Usuario();
        $this->categorias   = new Categoria();
    }

    /** Reporte general del sistema. */
    public function dashboard(): void
    {
        $indicadores = $this->estadisticas->panelAdministrativo();
        $catalogo    = $this->libros->resumenStock();
        $usuarios    = $this->usuarios->resumen();

        render('admin.dashboard', [
            'titulo'       => 'Dashboard',
            'conSidebar'   => true,
            'indicadores'  => $indicadores,
            'catalogo'     => $catalogo,
            'usuarios'     => $usuarios,
            'categorias'   => count($this->categorias->todas()),
            'serie'        => $this->estadisticas->prestamosUltimosMeses(6),
            'estadoCatalogo' => $this->estadisticas->estadoCatalogo(),
        ]);
    }
}
