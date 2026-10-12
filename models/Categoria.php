<?php
/**
 * BiblioTech — Modelo de categorías (RF06, RF08).
 */

declare(strict_types=1);

class Categoria extends Model
{
    protected string $tabla = 'categorias';

    /**
     * Todas las categorías con la cantidad de libros que las usan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conConteo(): array
    {
        return $this->consultar(
            'SELECT c.id, c.nombre, c.descripcion,
                    COUNT(l.id) AS total_libros
               FROM categorias c
               LEFT JOIN libros l ON l.id_categoria = c.id
              GROUP BY c.id, c.nombre, c.descripcion
              ORDER BY c.nombre ASC'
        );
    }

    /**
     * Catálogo simple de categorías (para selectores y filtros).
     *
     * @return array<int, array<string, mixed>>
     */
    public function todas(): array
    {
        return $this->consultar('SELECT id, nombre, descripcion FROM categorias ORDER BY nombre ASC');
    }

    /**
     * Una categoría por identificador.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT id, nombre, descripcion FROM categorias WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * Categorías con al menos un libro disponible (usada en la página principal).
     *
     * @return array<int, array<string, mixed>>
     */
    public function conDisponibles(): array
    {
        return $this->consultar(
            'SELECT c.id, c.nombre, COUNT(l.id) AS total_libros
               FROM categorias c
               INNER JOIN libros l ON l.id_categoria = c.id AND l.stock_disponible > 0
              GROUP BY c.id, c.nombre
              ORDER BY c.nombre ASC'
        );
    }

    /** Indica si ya existe una categoría con ese nombre. */
    public function nombreRegistrado(string $nombre, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categorias WHERE nombre = :nombre';
        $parametros = [':nombre' => $nombre];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros[':id'] = $exceptoId;
        }

        return (int) $this->escalar($sql, $parametros) > 0;
    }

    /** Crea una categoría y devuelve su identificador. */
    public function crear(string $nombre, ?string $descripcion): int
    {
        $this->ejecutar(
            'INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)',
            [
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion !== null && $descripcion !== '' ? $descripcion : null,
            ]
        );

        return $this->ultimoId();
    }

    /** Actualiza nombre y descripción de una categoría. */
    public function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $this->ejecutar(
            'UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id = :id',
            [
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion !== null && $descripcion !== '' ? $descripcion : null,
                ':id'          => $id,
            ]
        );
    }

    /** Cantidad de libros asociados a una categoría. */
    public function contarLibros(int $id): int
    {
        return (int) $this->escalar('SELECT COUNT(*) FROM libros WHERE id_categoria = :id', [':id' => $id]);
    }

    /**
     * Elimina una categoría. Devuelve false si tiene libros asociados.
     * Se evita borrar para no perder la clasificación del catálogo.
     */
    public function eliminar(int $id): bool
    {
        if ($this->contarLibros($id) > 0) {
            return false;
        }

        $this->ejecutar('DELETE FROM categorias WHERE id = :id', [':id' => $id]);

        return true;
    }
}
