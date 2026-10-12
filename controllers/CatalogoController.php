<?php
/**
 * BiblioTech — Catálogo de libros (RF04, RF05, RF06, RF07).
 *
 * Disponible para visitantes y para los tres roles. Los estudiantes y el
 * bibliotecario consultan el mismo catálogo; el administrador además lo edita.
 */

declare(strict_types=1);

class CatalogoController extends Controller
{
    private Libro $libros;
    private Categoria $categorias;

    public function __construct()
    {
        $this->libros     = new Libro();
        $this->categorias = new Categoria();
    }

    /** Listado con búsqueda por título/autor/ISBN y filtro por categoría. */
    public function index(): void
    {
        $filtros = $this->filtrosDesdePeticion();
        $pagina  = max(1, entrada_int('pagina', 1));

        $resultado = $this->libros->buscar($filtros, $pagina);

        render('catalogo.index', [
            'titulo'      => 'Catálogo',
            'resultado'   => $resultado,
            'filtros'     => $filtros,
            'categorias'  => $this->categorias->todas(),
            'parametros'  => $this->parametrosPaginacion($filtros),
        ]);
    }

    /** Ficha detallada de un libro. */
    public function ver(): void
    {
        $id    = entrada_int('id');
        $libro = $this->libros->obtener($id);

        if ($libro === null) {
            responder_error(404, 'El libro solicitado no existe en el catálogo.');

            return;
        }

        render('catalogo.ver', [
            'titulo' => (string) $libro['titulo'],
            'libro'  => $libro,
        ]);
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /**
     * Normaliza los filtros recibidos por la URL.
     *
     * @return array{texto: string, id_categoria: int, disponibilidad: string, orden: string}
     */
    private function filtrosDesdePeticion(): array
    {
        $disponibilidad = (string) entrada('disponibilidad', 'todos');

        if (!in_array($disponibilidad, ['todos', 'disponibles', 'agotados'], true)) {
            $disponibilidad = 'todos';
        }

        $orden = (string) entrada('orden', 'titulo');

        if (!in_array($orden, ['titulo', 'autor', 'reciente', 'anio'], true)) {
            $orden = 'titulo';
        }

        return [
            'texto'          => (string) entrada('q', ''),
            'id_categoria'   => max(0, entrada_int('categoria')),
            'disponibilidad' => $disponibilidad,
            'orden'          => $orden,
        ];
    }

    /**
     * Parámetros que deben conservarse al cambiar de página.
     *
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    private function parametrosPaginacion(array $filtros): array
    {
        $parametros = [];

        if (($filtros['texto'] ?? '') !== '') {
            $parametros['q'] = $filtros['texto'];
        }

        if ((int) ($filtros['id_categoria'] ?? 0) > 0) {
            $parametros['categoria'] = (int) $filtros['id_categoria'];
        }

        if (($filtros['disponibilidad'] ?? 'todos') !== 'todos') {
            $parametros['disponibilidad'] = $filtros['disponibilidad'];
        }

        if (($filtros['orden'] ?? 'titulo') !== 'titulo') {
            $parametros['orden'] = $filtros['orden'];
        }

        return $parametros;
    }
}
