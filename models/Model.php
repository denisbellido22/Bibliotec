<?php
/**
 * BiblioTech — Modelo base.
 *
 * Todos los accesos a datos pasan por consultas preparadas con parámetros
 * nombrados, evitando la inyección SQL (RNF02).
 */

declare(strict_types=1);

abstract class Model
{
    protected PDO $db;

    /** Nombre de la tabla principal del modelo. */
    protected string $tabla = '';

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Ejecuta una consulta preparada y devuelve todas las filas.
     *
     * @param array<string, mixed> $parametros
     * @return array<int, array<string, mixed>>
     */
    protected function consultar(string $sql, array $parametros = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Ejecuta una consulta y devuelve la primera fila (o null).
     *
     * @param array<string, mixed> $parametros
     * @return array<string, mixed>|null
     */
    protected function uno(string $sql, array $parametros = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Devuelve el valor de la primera columna de la primera fila.
     *
     * @param array<string, mixed> $parametros
     */
    protected function escalar(string $sql, array $parametros = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        $valor = $stmt->fetchColumn();

        return $valor === false ? null : $valor;
    }

    /**
     * Ejecuta una sentencia de escritura y devuelve las filas afectadas.
     *
     * @param array<string, mixed> $parametros
     */
    protected function ejecutar(string $sql, array $parametros = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->rowCount();
    }

    /** Último identificador insertado. */
    protected function ultimoId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
