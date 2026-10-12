<?php
/**
 * BiblioTech — Modelo de libros (RF03, RF05, RF06, RF07).
 *
 * Todas las consultas usan parámetros nombrados y JOIN con `categorias`
 * respetando la estructura real de la base de datos `Bibliotec`.
 */

declare(strict_types=1);

class Libro extends Model
{
    protected string $tabla = 'libros';

    /** Registros por página del catálogo. */
    public const POR_PAGINA = 12;

    /** Órdenes admitidos en el catálogo. */
    private const ORDENES = [
        'titulo'   => 'l.titulo ASC',
        'autor'    => 'l.autor ASC',
        'reciente' => 'l.created_at DESC',
        'anio'     => 'l.anio DESC, l.titulo ASC',
    ];

    /**
     * Busca libros aplicando texto libre, categoría y disponibilidad.
     *
     * @param array{texto?: string, id_categoria?: int, disponibilidad?: string, orden?: string} $filtros
     * @return array{datos: array<int, array<string, mixed>>, total: int, paginas: int, pagina: int, por_pagina: int}
     */
    public function buscar(array $filtros = [], int $pagina = 1, int $porPagina = self::POR_PAGINA): array
    {
        $condiciones = [];
        $parametros  = [];

        /*
         * RF05: búsqueda por título, autor o ISBN.
         *
         * Con ATTR_EMULATE_PREPARES = false PDO no admite repetir el mismo
         * marcador nombrado, por eso cada LIKE usa su propio parámetro.
         */
        $texto = trim((string) ($filtros['texto'] ?? ''));
        if ($texto !== '') {
            $condiciones[] = '(l.titulo LIKE :texto_titulo OR l.autor LIKE :texto_autor OR l.isbn LIKE :texto_isbn)';
            $comodin = '%' . $texto . '%';
            $parametros[':texto_titulo'] = $comodin;
            $parametros[':texto_autor']  = $comodin;
            $parametros[':texto_isbn']   = $comodin;
        }

        // RF06: filtrado por categoría.
        $categoria = (int) ($filtros['id_categoria'] ?? 0);
        if ($categoria > 0) {
            $condiciones[] = 'l.id_categoria = :categoria';
            $parametros[':categoria'] = $categoria;
        }

        // RF07: control del stock disponible.
        $disponibilidad = (string) ($filtros['disponibilidad'] ?? 'todos');
        if ($disponibilidad === 'disponibles') {
            $condiciones[] = 'l.stock_disponible > 0';
        } elseif ($disponibilidad === 'agotados') {
            $condiciones[] = 'l.stock_disponible = 0';
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        // Total de coincidencias (para la paginación).
        $total = (int) $this->escalar(
            'SELECT COUNT(*) FROM libros l' . $where,
            $parametros
        );

        $porPagina = max(1, min(48, $porPagina));
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = max(1, min($pagina, $paginas));
        $desplazamiento = ($pagina - 1) * $porPagina;

        $orden = self::ORDENES[(string) ($filtros['orden'] ?? 'titulo')] ?? self::ORDENES['titulo'];

        $sql = 'SELECT l.id, l.titulo, l.autor, l.isbn, l.editorial, l.anio, l.portada_url,
                       l.sinopsis, l.stock_total, l.stock_disponible, l.id_categoria,
                       c.nombre AS categoria
                  FROM libros l
                  LEFT JOIN categorias c ON c.id = l.id_categoria'
            . $where
            . ' ORDER BY ' . $orden
            . ' LIMIT :limite OFFSET :desplazamiento';

        $stmt = $this->db->prepare($sql);

        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        // Los límites deben enviarse como enteros para que MySQL los acepte.
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'datos'      => $stmt->fetchAll(),
            'total'      => $total,
            'paginas'    => $paginas,
            'pagina'     => $pagina,
            'por_pagina' => $porPagina,
        ];
    }

    /**
     * Libros destacados para la página principal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function destacados(int $limite = 4): array
    {
        $limite = max(1, min(12, $limite));

        return $this->consultar(
            'SELECT l.id, l.titulo, l.autor, l.isbn, l.portada_url, l.stock_total, l.stock_disponible,
                    c.nombre AS categoria
               FROM libros l
               LEFT JOIN categorias c ON c.id = l.id_categoria
              ORDER BY l.created_at DESC, l.id DESC
              LIMIT ' . $limite
        );
    }

    /**
     * Detalle de un libro por su identificador.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT l.*, c.nombre AS categoria, c.descripcion AS categoria_descripcion
               FROM libros l
               LEFT JOIN categorias c ON c.id = l.id_categoria
              WHERE l.id = :id',
            [':id' => $id]
        );
    }

    /** Indica si un ISBN ya está registrado (excluyendo un libro concreto). */
    public function isbnRegistrado(string $isbn, ?int $exceptoId = null): bool
    {
        if (trim($isbn) === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM libros WHERE isbn = :isbn';
        $parametros = [':isbn' => $isbn];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros[':id'] = $exceptoId;
        }

        return (int) $this->escalar($sql, $parametros) > 0;
    }

    /**
     * Registra un libro nuevo.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO libros
                    (titulo, autor, isbn, id_categoria, editorial, anio, portada_url,
                     sinopsis, stock_total, stock_disponible)
                VALUES
                    (:titulo, :autor, :isbn, :id_categoria, :editorial, :anio, :portada_url,
                     :sinopsis, :stock_total, :stock_disponible)';

        $this->ejecutar($sql, [
            ':titulo'           => $datos['titulo'],
            ':autor'            => $datos['autor'],
            ':isbn'             => $datos['isbn'] !== '' ? $datos['isbn'] : null,
            ':id_categoria'     => $datos['id_categoria'] !== null ? (int) $datos['id_categoria'] : null,
            ':editorial'        => $datos['editorial'] !== '' ? $datos['editorial'] : null,
            ':anio'             => $datos['anio'] !== null ? (int) $datos['anio'] : null,
            ':portada_url'      => $datos['portada_url'] !== '' ? $datos['portada_url'] : null,
            ':sinopsis'         => $datos['sinopsis'] !== '' ? $datos['sinopsis'] : null,
            ':stock_total'      => (int) $datos['stock_total'],
            ':stock_disponible' => (int) $datos['stock_disponible'],
        ]);

        return $this->ultimoId();
    }

    /**
     * Actualiza los datos de un libro.
     *
     * El stock disponible se recalcula cuando cambia el stock total para no
     * dejar ejemplares disponibles por encima del total.
     *
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos): void
    {
        $actual = $this->uno('SELECT stock_total, stock_disponible FROM libros WHERE id = :id', [':id' => $id]);

        if ($actual === null) {
            return;
        }

        $totalAnterior     = (int) $actual['stock_total'];
        $nuevoTotal        = (int) $datos['stock_total'];
        $disponibleAnterior = (int) $actual['stock_disponible'];

        if (array_key_exists('stock_disponible', $datos) && $datos['stock_disponible'] !== null) {
            $nuevoDisponible = (int) $datos['stock_disponible'];
        } else {
            // Se conserva la diferencia: préstamos en curso siguen ocupando ejemplares.
            $prestados = max(0, $totalAnterior - $disponibleAnterior);
            $nuevoDisponible = max(0, $nuevoTotal - $prestados);
        }

        $nuevoDisponible = max(0, min($nuevoDisponible, $nuevoTotal));

        $sql = 'UPDATE libros
                   SET titulo = :titulo,
                       autor = :autor,
                       isbn = :isbn,
                       id_categoria = :id_categoria,
                       editorial = :editorial,
                       anio = :anio,
                       portada_url = :portada_url,
                       sinopsis = :sinopsis,
                       stock_total = :stock_total,
                       stock_disponible = :stock_disponible
                 WHERE id = :id';

        $this->ejecutar($sql, [
            ':titulo'           => $datos['titulo'],
            ':autor'            => $datos['autor'],
            ':isbn'             => $datos['isbn'] !== '' ? $datos['isbn'] : null,
            ':id_categoria'     => $datos['id_categoria'] !== null ? (int) $datos['id_categoria'] : null,
            ':editorial'        => $datos['editorial'] !== '' ? $datos['editorial'] : null,
            ':anio'             => $datos['anio'] !== null ? (int) $datos['anio'] : null,
            ':portada_url'      => $datos['portada_url'] !== '' ? $datos['portada_url'] : null,
            ':sinopsis'         => $datos['sinopsis'] !== '' ? $datos['sinopsis'] : null,
            ':stock_total'      => $nuevoTotal,
            ':stock_disponible' => $nuevoDisponible,
            ':id'               => $id,
        ]);
    }

    /** Elimina un libro. Devuelve false si tiene préstamos o reservas asociadas. */
    public function eliminar(int $id): bool
    {
        // Cada subconsulta necesita su propio marcador (preparadas nativas).
        $movimientos = (int) $this->escalar(
            'SELECT (SELECT COUNT(*) FROM prestamos WHERE id_libro = :id_prestamo)
                  + (SELECT COUNT(*) FROM reservas WHERE id_libro = :id_reserva)',
            [':id_prestamo' => $id, ':id_reserva' => $id]
        );

        if ($movimientos > 0) {
            return false;
        }

        $this->ejecutar('DELETE FROM libros WHERE id = :id', [':id' => $id]);

        return true;
    }

    /**
     * Motivos por los que un libro no puede eliminarse (para informar al usuario).
     *
     * @return array<int, string>
     */
    public function motivosBloqueo(int $id): array
    {
        $prestamos = (int) $this->escalar('SELECT COUNT(*) FROM prestamos WHERE id_libro = :id', [':id' => $id]);
        $reservas  = (int) $this->escalar('SELECT COUNT(*) FROM reservas WHERE id_libro = :id', [':id' => $id]);

        $motivos = [];

        if ($prestamos > 0) {
            $motivos[] = $prestamos . ' préstamo(s) registrado(s)';
        }

        if ($reservas > 0) {
            $motivos[] = $reservas . ' reserva(s) registrada(s)';
        }

        return $motivos;
    }

    /* ------------------------------------------------------------------
     * Operaciones de stock usadas por préstamos y devoluciones
     * ------------------------------------------------------------------ */

    /**
     * Descuenta un ejemplar del stock disponible de forma atómica.
     * Debe invocarse dentro de una transacción.
     */
    public function reservarEjemplar(int $id): bool
    {
        $afectadas = $this->ejecutar(
            'UPDATE libros
                SET stock_disponible = stock_disponible - 1
              WHERE id = :id AND stock_disponible > 0',
            [':id' => $id]
        );

        return $afectadas === 1;
    }

    /** Devuelve un ejemplar al stock disponible. Debe usarse en transacción. */
    public function liberarEjemplar(int $id): void
    {
        $this->ejecutar(
            'UPDATE libros
                SET stock_disponible = LEAST(stock_disponible + 1, stock_total)
              WHERE id = :id',
            [':id' => $id]
        );
    }

    /* ------------------------------------------------------------------
     * Totales para reportes (RF02)
     * ------------------------------------------------------------------ */

    /**
     * Resumen general del catálogo.
     *
     * @return array{titulos: int, ejemplares: int, disponibles: int, prestados: int, agotados: int}
     */
    public function resumenStock(): array
    {
        $fila = $this->uno(
            'SELECT COUNT(*) AS titulos,
                    COALESCE(SUM(stock_total), 0) AS ejemplares,
                    COALESCE(SUM(stock_disponible), 0) AS disponibles,
                    COALESCE(SUM(stock_total - stock_disponible), 0) AS prestados,
                    COALESCE(SUM(CASE WHEN stock_disponible = 0 THEN 1 ELSE 0 END), 0) AS agotados
               FROM libros'
        ) ?? [];

        return [
            'titulos'     => (int) ($fila['titulos'] ?? 0),
            'ejemplares'  => (int) ($fila['ejemplares'] ?? 0),
            'disponibles' => (int) ($fila['disponibles'] ?? 0),
            'prestados'   => (int) ($fila['prestados'] ?? 0),
            'agotados'    => (int) ($fila['agotados'] ?? 0),
        ];
    }
}
