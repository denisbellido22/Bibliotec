<?php
/**
 * BiblioTech — Panel del bibliotecario.
 *
 * Reúne las operaciones cotidianas: consulta del catálogo (RF04), control del
 * stock (RF07) y supervisión de préstamos, reservas y multas, sin acceso a la
 * administración de usuarios ni a la configuración del sistema.
 */

declare(strict_types=1);

class BibliotecarioController extends Controller
{
    private Libro $libros;
    private Prestamo $prestamos;
    private Reserva $reservas;
    private Multa $multas;

    public function __construct()
    {
        $this->libros    = new Libro();
        $this->prestamos = new Prestamo();
        $this->reservas  = new Reserva();
        $this->multas    = new Multa();
    }

    /** Resumen operativo del día. */
    public function panel(): void
    {
        $this->exigirRol('bibliotecario');

        $this->prestamos->actualizarVencidos();
        $this->reservas->actualizarExpiradas();

        $vencidos = array_values(array_filter(
            $this->prestamos->buscar(),
            static fn (array $p): bool => in_array($p['estado'], ['activo', 'vencido'], true)
                && (int) $p['dias_retraso'] > 0
        ));

        $this->vista('bibliotecario.panel', [
            'titulo'          => 'Panel biblioteca',
            'conSidebar'      => true,
            'catalogo'        => $this->libros->resumenStock(),
            'prestamos'       => $this->prestamos->resumen(),
            'reservas'        => $this->reservas->resumen(),
            'multas'          => $this->multas->resumen(),
            'vencidos'        => array_slice($vencidos, 0, 6),
        ]);
    }

    /** Consulta del catálogo en modo lectura (RF04, RF05, RF06, RF07). */
    public function libros(): void
    {
        $this->exigirRol('bibliotecario');

        $filtros = [
            'texto'          => (string) entrada('q', ''),
            'id_categoria'   => max(0, entrada_int('categoria')),
            'disponibilidad' => in_array(entrada('disponibilidad', 'todos'), ['todos', 'disponibles', 'agotados'], true)
                ? (string) entrada('disponibilidad', 'todos')
                : 'todos',
            'orden'          => 'titulo',
        ];

        $resultado = $this->libros->buscar($filtros, max(1, entrada_int('pagina', 1)), 20);

        $this->vista('bibliotecario.libros', [
            'titulo'     => 'Libros',
            'conSidebar' => true,
            'resultado'  => $resultado,
            'filtros'    => $filtros,
            'categorias' => (new Categoria())->todas(),
            'parametros' => array_filter([
                'q'              => $filtros['texto'] !== '' ? $filtros['texto'] : null,
                'categoria'      => $filtros['id_categoria'] > 0 ? $filtros['id_categoria'] : null,
                'disponibilidad' => $filtros['disponibilidad'] !== 'todos' ? $filtros['disponibilidad'] : null,
            ], static fn ($valor): bool => $valor !== null),
        ]);
    }
}
