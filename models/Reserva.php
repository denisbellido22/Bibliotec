<?php
/**
 * BiblioTech — Modelo de reservas.
 *
 * Reglas aplicadas al reservar:
 *   - El libro debe existir y tener ejemplares disponibles.
 *   - El usuario no puede duplicar una reserva pendiente del mismo título.
 *   - No se permite reservar con multas pendientes.
 *   - Existe un límite de reservas activas simultáneas.
 */

declare(strict_types=1);

class Reserva extends Model
{
    protected string $tabla = 'reservas';

    /** Estados válidos, según el ENUM de la tabla `reservas`. */
    public const ESTADOS = ['pendiente', 'confirmada', 'expirada', 'cancelada'];

    /**
     * Listado de reservas con datos del usuario y del libro.
     *
     * @param array{texto?: string, estado?: string, id_usuario?: int, id_libro?: int} $filtros
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
            $condiciones[] = 'r.estado = :estado';
            $parametros[':estado'] = $estado;
        }

        if ((int) ($filtros['id_usuario'] ?? 0) > 0) {
            $condiciones[] = 'r.id_usuario = :id_usuario';
            $parametros[':id_usuario'] = (int) $filtros['id_usuario'];
        }

        if ((int) ($filtros['id_libro'] ?? 0) > 0) {
            $condiciones[] = 'r.id_libro = :id_libro';
            $parametros[':id_libro'] = (int) $filtros['id_libro'];
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return $this->consultar(
            'SELECT r.id, r.id_usuario, r.id_libro, r.fecha_reserva, r.fecha_expiracion, r.estado,
                    u.nombre AS usuario, u.email AS usuario_email,
                    l.titulo AS libro, l.stock_total, l.stock_disponible,
                    (SELECT p.id FROM prestamos p WHERE p.id_reserva = r.id LIMIT 1) AS id_prestamo
               FROM reservas r
               INNER JOIN usuarios u ON u.id = r.id_usuario
               INNER JOIN libros l ON l.id = r.id_libro'
            . $where
            . ' ORDER BY (r.estado = "pendiente") DESC, r.fecha_reserva DESC',
            $parametros
        );
    }

    /**
     * Una reserva con sus datos relacionados.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT r.*, u.nombre AS usuario, u.email AS usuario_email,
                    l.titulo AS libro, l.stock_disponible
               FROM reservas r
               INNER JOIN usuarios u ON u.id = r.id_usuario
               INNER JOIN libros l ON l.id = r.id_libro
              WHERE r.id = :id',
            [':id' => $id]
        );
    }

    /** Reserva pendiente de un usuario para un libro concreto. */
    public function pendienteDe(int $idUsuario, int $idLibro): ?int
    {
        $id = $this->escalar(
            'SELECT id FROM reservas
              WHERE id_usuario = :id_usuario AND id_libro = :id_libro AND estado = "pendiente"
              LIMIT 1',
            [':id_usuario' => $idUsuario, ':id_libro' => $idLibro]
        );

        return $id === null ? null : (int) $id;
    }

    /** Reservas pendientes o confirmadas de un usuario. */
    public function activasDeUsuario(int $idUsuario): int
    {
        return (int) $this->escalar(
            'SELECT COUNT(*) FROM reservas
              WHERE id_usuario = :id AND estado IN ("pendiente", "confirmada")',
            [':id' => $idUsuario]
        );
    }

    /**
     * Registra una reserva aplicando las reglas de negocio.
     *
     * @return array{ok: bool, mensaje: string, id: int|null}
     */
    public function registrar(int $idUsuario, int $idLibro): array
    {
        $libro = (new Libro())->obtener($idLibro);

        if ($libro === null) {
            return ['ok' => false, 'mensaje' => 'El libro indicado no existe.', 'id' => null];
        }

        if ((int) $libro['stock_disponible'] <= 0) {
            return [
                'ok'      => false,
                'mensaje' => 'No hay ejemplares disponibles de «' . $libro['titulo'] . '», no es posible reservarlo.',
                'id'      => null,
            ];
        }

        if ($this->pendienteDe($idUsuario, $idLibro) !== null) {
            return [
                'ok'      => false,
                'mensaje' => 'Ya tienes una reserva pendiente de este libro.',
                'id'      => null,
            ];
        }

        if ((new Multa())->tienePendientes($idUsuario)) {
            return [
                'ok'      => false,
                'mensaje' => 'No puedes reservar mientras tengas multas pendientes de pago.',
                'id'      => null,
            ];
        }

        $maximo = (int) config('reservas.max_activas', 3);

        if ($this->activasDeUsuario($idUsuario) >= $maximo) {
            return [
                'ok'      => false,
                'mensaje' => 'Alcanzaste el límite de ' . $maximo . ' reservas activas simultáneas.',
                'id'      => null,
            ];
        }

        $dias = max(1, (int) config('reservas.dias_vigencia', 3));

        try {
            $this->ejecutar(
                'INSERT INTO reservas (id_usuario, id_libro, fecha_reserva, fecha_expiracion, estado)
                 VALUES (:id_usuario, :id_libro, NOW(), DATE_ADD(NOW(), INTERVAL :dias DAY), "pendiente")',
                [
                    ':id_usuario' => $idUsuario,
                    ':id_libro'   => $idLibro,
                    ':dias'       => $dias,
                ]
            );

            return [
                'ok'      => true,
                'mensaje' => 'Reserva registrada. Tienes ' . $dias . ' días para retirar «' . $libro['titulo'] . '».',
                'id'      => $this->ultimoId(),
            ];
        } catch (Throwable $e) {
            error_log('[BiblioTech] Error al registrar reserva: ' . $e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo registrar la reserva. Intenta nuevamente.', 'id' => null];
        }
    }

    /**
     * Cancela una reserva. Si se indica $idUsuario, sólo se permite sobre las
     * reservas de ese usuario (control de acceso por registro).
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function cancelar(int $id, ?int $idUsuario = null): array
    {
        $reserva = $this->obtener($id);

        if ($reserva === null) {
            return ['ok' => false, 'mensaje' => 'La reserva indicada no existe.'];
        }

        if ($idUsuario !== null && (int) $reserva['id_usuario'] !== $idUsuario) {
            return ['ok' => false, 'mensaje' => 'Esa reserva no te pertenece.'];
        }

        if ($reserva['estado'] !== 'pendiente') {
            return ['ok' => false, 'mensaje' => 'Sólo pueden cancelarse reservas pendientes.'];
        }

        $this->ejecutar(
            'UPDATE reservas SET estado = "cancelada" WHERE id = :id AND estado = "pendiente"',
            [':id' => $id]
        );

        return ['ok' => true, 'mensaje' => 'La reserva de «' . $reserva['libro'] . '» quedó cancelada.'];
    }

    /**
     * Confirma una reserva (entrega del ejemplar).
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function confirmar(int $id): array
    {
        $reserva = $this->obtener($id);

        if ($reserva === null) {
            return ['ok' => false, 'mensaje' => 'La reserva indicada no existe.'];
        }

        if ($reserva['estado'] !== 'pendiente') {
            return ['ok' => false, 'mensaje' => 'Sólo pueden confirmarse reservas pendientes.'];
        }

        $this->ejecutar('UPDATE reservas SET estado = "confirmada" WHERE id = :id', [':id' => $id]);

        return ['ok' => true, 'mensaje' => 'La reserva de «' . $reserva['libro'] . '» quedó confirmada.'];
    }

    /**
     * Marca como expiradas las reservas pendientes fuera de plazo.
     * Es idempotente.
     */
    public function actualizarExpiradas(): int
    {
        return $this->ejecutar(
            'UPDATE reservas
                SET estado = "expirada"
              WHERE estado = "pendiente" AND fecha_expiracion < NOW()'
        );
    }

    /**
     * Resumen de reservas para paneles.
     *
     * @return array{total: int, pendientes: int, confirmadas: int, expiradas: int, canceladas: int}
     */
    public function resumen(?int $idUsuario = null): array
    {
        $condicion  = $idUsuario !== null ? ' WHERE id_usuario = :id' : '';
        $parametros = $idUsuario !== null ? [':id' => $idUsuario] : [];

        $fila = $this->uno(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(estado = "pendiente"), 0) AS pendientes,
                    COALESCE(SUM(estado = "confirmada"), 0) AS confirmadas,
                    COALESCE(SUM(estado = "expirada"), 0) AS expiradas,
                    COALESCE(SUM(estado = "cancelada"), 0) AS canceladas
               FROM reservas'
            . $condicion,
            $parametros
        ) ?? [];

        return [
            'total'       => (int) ($fila['total'] ?? 0),
            'pendientes'  => (int) ($fila['pendientes'] ?? 0),
            'confirmadas' => (int) ($fila['confirmadas'] ?? 0),
            'expiradas'   => (int) ($fila['expiradas'] ?? 0),
            'canceladas'  => (int) ($fila['canceladas'] ?? 0),
        ];
    }
}
