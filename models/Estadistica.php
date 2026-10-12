<?php
/**
 * BiblioTech — Estadísticas y reportes (RF02, RF13).
 *
 * Todas las cifras se calculan con consultas de agregación sobre la base de
 * datos real `Bibliotec`: el panel no muestra valores estáticos ni simulados.
 */

declare(strict_types=1);

class Estadistica extends Model
{
    /**
     * Indicadores principales del panel administrativo.
     *
     * @return array<string, int|float>
     */
    public function panelAdministrativo(): array
    {
        $inicioMes = date('Y-m-01 00:00:00');

        $hoy = date('Y-m-d');

        $prestamos = $this->uno(
            'SELECT COUNT(*) AS activos,
                    COALESCE(SUM(estado = "activo" AND fecha_devolucion_esperada < :hoy), 0) AS vencidos,
                    COALESCE(SUM(fecha_prestamo >= :inicio_mes), 0) AS del_mes,
                    COALESCE(SUM(estado = "devuelto"), 0) AS devueltos,
                    COUNT(*) AS total
               FROM prestamos',
            [':hoy' => $hoy, ':inicio_mes' => date('Y-m-d', strtotime($inicioMes))]
        ) ?? [];

        $reservas = $this->uno(
            'SELECT COALESCE(SUM(estado = "pendiente"), 0) AS pendientes,
                    COALESCE(SUM(estado = "confirmada"), 0) AS confirmadas,
                    COUNT(*) AS total
               FROM reservas'
        ) ?? [];

        $multas = $this->uno(
            'SELECT COALESCE(SUM(estado = "pendiente"), 0) AS pendientes,
                    COALESCE(SUM(CASE WHEN estado = "pendiente" THEN monto ELSE 0 END), 0) AS monto_pendiente,
                    COALESCE(SUM(CASE WHEN estado = "pagada" AND fecha_pago >= :inicio_mes THEN monto ELSE 0 END), 0) AS cobrado_mes,
                    COUNT(*) AS total
               FROM multas',
            [':inicio_mes' => $inicioMes]
        ) ?? [];

        return [
            'prestamos_activos'    => (int) ($prestamos['activos'] ?? 0),
            'prestamos_vencidos'   => (int) ($prestamos['vencidos'] ?? 0),
            'prestamos_mes'        => (int) ($prestamos['del_mes'] ?? 0),
            'prestamos_devueltos'  => (int) ($prestamos['devueltos'] ?? 0),
            'prestamos_total'      => (int) ($prestamos['total'] ?? 0),
            'reservas_pendientes'  => (int) ($reservas['pendientes'] ?? 0),
            'reservas_confirmadas' => (int) ($reservas['confirmadas'] ?? 0),
            'reservas_total'       => (int) ($reservas['total'] ?? 0),
            'multas_pendientes'    => (int) ($multas['pendientes'] ?? 0),
            'multas_monto_pendiente' => (float) ($multas['monto_pendiente'] ?? 0),
            'multas_cobrado_mes'   => (float) ($multas['cobrado_mes'] ?? 0),
            'multas_total'         => (int) ($multas['total'] ?? 0),
        ];
    }

    /**
     * Cantidad de préstamos por mes de los últimos N meses.
     *
     * @return array<int, array{periodo: string, etiqueta: string, total: int}>
     */
    public function prestamosUltimosMeses(int $meses = 6): array
    {
        $meses = max(1, min(24, $meses));

        $desde = date('Y-m-01', strtotime('-' . ($meses - 1) . ' months'));

        $filas = $this->consultar(
            'SELECT DATE_FORMAT(fecha_prestamo, "%Y-%m") AS periodo, COUNT(*) AS total
               FROM prestamos
              WHERE fecha_prestamo >= :desde
              GROUP BY periodo',
            [':desde' => $desde]
        );

        $agrupado = [];
        foreach ($filas as $fila) {
            $agrupado[(string) $fila['periodo']] = (int) $fila['total'];
        }

        $nombres = [
            1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
        ];

        $serie = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $marca = strtotime('-' . $i . ' months');
            $periodo = date('Y-m', $marca);

            $serie[] = [
                'periodo'  => $periodo,
                'etiqueta' => $nombres[(int) date('n', $marca)] . ' ' . date('y', $marca),
                'total'    => $agrupado[$periodo] ?? 0,
            ];
        }

        return $serie;
    }

    /**
     * Préstamos por categoría, útil para los reportes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prestamosPorCategoria(): array
    {
        return $this->consultar(
            'SELECT COALESCE(c.nombre, "Sin categoría") AS categoria,
                    COUNT(p.id) AS total
               FROM prestamos p
               INNER JOIN libros l ON l.id = p.id_libro
               LEFT JOIN categorias c ON c.id = l.id_categoria
              GROUP BY c.nombre
              ORDER BY total DESC'
        );
    }

    /**
     * Libros más prestados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function librosMasPrestados(int $limite = 10): array
    {
        $limite = max(1, min(50, $limite));

        return $this->consultar(
            'SELECT l.id, l.titulo, l.autor,
                    COUNT(p.id) AS total_prestamos
               FROM prestamos p
               INNER JOIN libros l ON l.id = p.id_libro
              GROUP BY l.id, l.titulo, l.autor
              ORDER BY total_prestamos DESC, l.titulo ASC
              LIMIT ' . $limite
        );
    }

    /**
     * Ocupación del catálogo por categoría.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogoPorCategoria(): array
    {
        return $this->consultar(
            'SELECT c.nombre AS categoria,
                    COUNT(l.id) AS titulos,
                    COALESCE(SUM(l.stock_total), 0) AS ejemplares,
                    COALESCE(SUM(l.stock_disponible), 0) AS disponibles
               FROM categorias c
               LEFT JOIN libros l ON l.id_categoria = c.id
              GROUP BY c.id, c.nombre
              ORDER BY titulos DESC, c.nombre ASC'
        );
    }

    /**
     * Estado del catálogo según disponibilidad.
     *
     * @return array{disponibles: int, parciales: int, agotados: int}
     */
    public function estadoCatalogo(): array
    {
        $fila = $this->uno(
            'SELECT COALESCE(SUM(stock_disponible = 0), 0) AS agotados,
                    COALESCE(SUM(stock_disponible > 0 AND stock_disponible < stock_total), 0) AS parciales,
                    COALESCE(SUM(stock_disponible = stock_total AND stock_total > 0), 0) AS disponibles
               FROM libros'
        ) ?? [];

        return [
            'disponibles' => (int) ($fila['disponibles'] ?? 0),
            'parciales'   => (int) ($fila['parciales'] ?? 0),
            'agotados'    => (int) ($fila['agotados'] ?? 0),
        ];
    }

    /**
     * Montos cobrados por mes en multas pagadas.
     *
     * @return array<int, array{periodo: string, etiqueta: string, total: float}>
     */
    public function cobrosUltimosMeses(int $meses = 6): array
    {
        $meses = max(1, min(24, $meses));

        $desde = date('Y-m-01', strtotime('-' . ($meses - 1) . ' months'));

        $filas = $this->consultar(
            'SELECT DATE_FORMAT(fecha_pago, "%Y-%m") AS periodo, COALESCE(SUM(monto), 0) AS total
               FROM multas
              WHERE estado = "pagada" AND fecha_pago IS NOT NULL AND fecha_pago >= :desde
              GROUP BY periodo',
            [':desde' => $desde]
        );

        $agrupado = [];
        foreach ($filas as $fila) {
            $agrupado[(string) $fila['periodo']] = (float) $fila['total'];
        }

        $nombres = [
            1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
        ];

        $serie = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $marca = strtotime('-' . $i . ' months');
            $periodo = date('Y-m', $marca);

            $serie[] = [
                'periodo'  => $periodo,
                'etiqueta' => $nombres[(int) date('n', $marca)] . ' ' . date('y', $marca),
                'total'    => (float) ($agrupado[$periodo] ?? 0),
            ];
        }

        return $serie;
    }
}
