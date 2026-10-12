<?php
/**
 * BiblioTech — Modelo de multas.
 *
 * Las multas se generan a partir de las devoluciones con retraso y se
 * actualizan sólo por personal autorizado. No se simulan pasarelas de pago:
 * el pago se registra como un cambio de estado con fecha.
 */

declare(strict_types=1);

class Multa extends Model
{
    protected string $tabla = 'multas';

    /** Estados válidos, según el ENUM de la tabla `multas`. */
    public const ESTADOS = ['pendiente', 'pagada', 'condonada'];

    /**
     * Listado de multas con datos del préstamo, usuario y libro.
     *
     * @param array{texto?: string, estado?: string, id_usuario?: int} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function buscar(array $filtros = []): array
    {
        $condiciones = [];
        $parametros  = [];

        $texto = trim((string) ($filtros['texto'] ?? ''));
        if ($texto !== '') {
            $condiciones[] = '(u.nombre LIKE :texto_usuario OR u.email LIKE :texto_email
                               OR l.titulo LIKE :texto_titulo)';
            $comodin = '%' . $texto . '%';
            $parametros[':texto_usuario'] = $comodin;
            $parametros[':texto_email']   = $comodin;
            $parametros[':texto_titulo']  = $comodin;
        }

        $estado = (string) ($filtros['estado'] ?? '');
        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $condiciones[] = 'm.estado = :estado';
            $parametros[':estado'] = $estado;
        }

        if ((int) ($filtros['id_usuario'] ?? 0) > 0) {
            $condiciones[] = 'p.id_usuario = :id_usuario';
            $parametros[':id_usuario'] = (int) $filtros['id_usuario'];
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return $this->consultar(
            'SELECT m.id, m.id_prestamo, m.monto, m.estado, m.fecha_generacion, m.fecha_pago,
                    p.id_usuario, p.fecha_prestamo, p.fecha_devolucion_esperada, p.fecha_devolucion_real,
                    u.nombre AS usuario, u.email AS usuario_email,
                    l.titulo AS libro,
                    DATEDIFF(COALESCE(p.fecha_devolucion_real, CURDATE()), p.fecha_devolucion_esperada) AS dias_retraso
               FROM multas m
               INNER JOIN prestamos p ON p.id = m.id_prestamo
               INNER JOIN usuarios u ON u.id = p.id_usuario
               INNER JOIN libros l ON l.id = p.id_libro'
            . $where
            . ' ORDER BY (m.estado = "pendiente") DESC, m.fecha_generacion DESC',
            $parametros
        );
    }

    /**
     * Una multa por identificador.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT m.*, p.id_usuario, p.id_libro, u.nombre AS usuario, u.email AS usuario_email,
                    l.titulo AS libro
               FROM multas m
               INNER JOIN prestamos p ON p.id = m.id_prestamo
               INNER JOIN usuarios u ON u.id = p.id_usuario
               INNER JOIN libros l ON l.id = p.id_libro
              WHERE m.id = :id',
            [':id' => $id]
        );
    }

    /**
     * Multa asociada a un préstamo (una como máximo).
     *
     * @return array<string, mixed>|null
     */
    public function porPrestamo(int $idPrestamo): ?array
    {
        return $this->uno(
            'SELECT id, id_prestamo, monto, estado, fecha_generacion, fecha_pago
               FROM multas
              WHERE id_prestamo = :id
              LIMIT 1',
            [':id' => $idPrestamo]
        );
    }

    /**
     * Genera la multa de un préstamo devuelto con retraso.
     *
     * Es idempotente: si el préstamo ya tiene multa no crea otra, así que
     * registrar dos veces la devolución no duplica el cargo.
     *
     * @return float|null Importe generado, o null si no corresponde multa.
     */
    public function generarPorRetraso(int $idPrestamo, int $diasRetraso): ?float
    {
        if ($diasRetraso <= 0) {
            return null;
        }

        if ($this->porPrestamo($idPrestamo) !== null) {
            return null;
        }

        $monto = calcular_multa($diasRetraso);

        if ($monto <= 0) {
            return null;
        }

        $this->ejecutar(
            'INSERT INTO multas (id_prestamo, monto, estado, fecha_generacion)
             VALUES (:id_prestamo, :monto, "pendiente", NOW())',
            [':id_prestamo' => $idPrestamo, ':monto' => $monto]
        );

        return $monto;
    }

    /**
     * Registra el pago de una multa pendiente.
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function registrarPago(int $id): array
    {
        $multa = $this->obtener($id);

        if ($multa === null) {
            return ['ok' => false, 'mensaje' => 'La multa indicada no existe.'];
        }

        if ($multa['estado'] === 'pagada') {
            return ['ok' => false, 'mensaje' => 'Esa multa ya figura como pagada: no se registra un pago duplicado.'];
        }

        if ($multa['estado'] === 'condonada') {
            return ['ok' => false, 'mensaje' => 'Esa multa fue condonada y no admite pago.'];
        }

        $this->ejecutar(
            'UPDATE multas
                SET estado = "pagada", fecha_pago = NOW()
              WHERE id = :id AND estado = "pendiente"',
            [':id' => $id]
        );

        return [
            'ok'      => true,
            'mensaje' => 'Pago registrado: ' . dinero((float) $multa['monto'])
                . ' por ' . $multa['usuario'] . '.',
        ];
    }

    /**
     * Condona una multa pendiente sin registrar cobro.
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function condonar(int $id): array
    {
        $multa = $this->obtener($id);

        if ($multa === null) {
            return ['ok' => false, 'mensaje' => 'La multa indicada no existe.'];
        }

        if ($multa['estado'] !== 'pendiente') {
            return ['ok' => false, 'mensaje' => 'Sólo pueden condonarse multas pendientes.'];
        }

        $this->ejecutar(
            'UPDATE multas SET estado = "condonada", fecha_pago = NULL WHERE id = :id',
            [':id' => $id]
        );

        return ['ok' => true, 'mensaje' => 'La multa de ' . $multa['usuario'] . ' quedó condonada.'];
    }

    /**
     * Resumen de multas para paneles y fichas de usuario.
     *
     * @return array{total: int, pendientes: int, pagadas: int, condonadas: int,
     *               monto_pendiente: float, monto_cobrado: float}
     */
    public function resumen(?int $idUsuario = null): array
    {
        $condicion  = $idUsuario !== null ? ' WHERE p.id_usuario = :id' : '';
        $parametros = $idUsuario !== null ? [':id' => $idUsuario] : [];

        $fila = $this->uno(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(m.estado = "pendiente"), 0) AS pendientes,
                    COALESCE(SUM(m.estado = "pagada"), 0) AS pagadas,
                    COALESCE(SUM(m.estado = "condonada"), 0) AS condonadas,
                    COALESCE(SUM(CASE WHEN m.estado = "pendiente" THEN m.monto ELSE 0 END), 0) AS monto_pendiente,
                    COALESCE(SUM(CASE WHEN m.estado = "pagada" THEN m.monto ELSE 0 END), 0) AS monto_cobrado
               FROM multas m
               INNER JOIN prestamos p ON p.id = m.id_prestamo'
            . $condicion,
            $parametros
        ) ?? [];

        return [
            'total'           => (int) ($fila['total'] ?? 0),
            'pendientes'      => (int) ($fila['pendientes'] ?? 0),
            'pagadas'         => (int) ($fila['pagadas'] ?? 0),
            'condonadas'      => (int) ($fila['condonadas'] ?? 0),
            'monto_pendiente' => (float) ($fila['monto_pendiente'] ?? 0),
            'monto_cobrado'   => (float) ($fila['monto_cobrado'] ?? 0),
        ];
    }

    /** Indica si el usuario tiene multas pendientes (impide nuevas reservas). */
    public function tienePendientes(int $idUsuario): bool
    {
        return (int) $this->escalar(
            'SELECT COUNT(*)
               FROM multas m
               INNER JOIN prestamos p ON p.id = m.id_prestamo
              WHERE p.id_usuario = :id AND m.estado = "pendiente"',
            [':id' => $idUsuario]
        ) > 0;
    }
}
