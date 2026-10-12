<?php
/**
 * BiblioTech — Modelo de préstamos y devoluciones (RF13, RNF07).
 *
 * Las operaciones que afectan al stock se ejecutan dentro de transacciones:
 * o se descuenta el ejemplar y se registra el préstamo, o no ocurre nada.
 */

declare(strict_types=1);

class Prestamo extends Model
{
    protected string $tabla = 'prestamos';

    /** Estados válidos, según el ENUM de la tabla `prestamos`. */
    public const ESTADOS = ['activo', 'devuelto', 'vencido', 'cancelado'];

    /**
     * Listado de préstamos con datos del usuario y del libro.
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
                               OR l.titulo LIKE :texto_titulo OR l.isbn LIKE :texto_isbn)';
            $comodin = '%' . $texto . '%';
            $parametros[':texto_usuario'] = $comodin;
            $parametros[':texto_email']   = $comodin;
            $parametros[':texto_titulo']  = $comodin;
            $parametros[':texto_isbn']    = $comodin;
        }

        $estado = (string) ($filtros['estado'] ?? '');
        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $condiciones[] = 'p.estado = :estado';
            $parametros[':estado'] = $estado;
        }

        if ((int) ($filtros['id_usuario'] ?? 0) > 0) {
            $condiciones[] = 'p.id_usuario = :id_usuario';
            $parametros[':id_usuario'] = (int) $filtros['id_usuario'];
        }

        if ((int) ($filtros['id_libro'] ?? 0) > 0) {
            $condiciones[] = 'p.id_libro = :id_libro';
            $parametros[':id_libro'] = (int) $filtros['id_libro'];
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return $this->consultar(
            'SELECT p.id, p.id_usuario, p.id_libro, p.id_reserva,
                    p.fecha_prestamo, p.fecha_devolucion_esperada, p.fecha_devolucion_real,
                    p.estado, p.created_at,
                    u.nombre AS usuario, u.email AS usuario_email,
                    l.titulo AS libro, l.isbn AS libro_isbn,
                    (SELECT m.id FROM multas m WHERE m.id_prestamo = p.id LIMIT 1) AS id_multa,
                    DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_retraso
               FROM prestamos p
               INNER JOIN usuarios u ON u.id = p.id_usuario
               INNER JOIN libros l ON l.id = p.id_libro'
            . $where
            . ' ORDER BY (p.estado IN ("activo", "vencido")) DESC, p.fecha_prestamo DESC, p.id DESC',
            $parametros
        );
    }

    /**
     * Un préstamo con sus datos relacionados.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        $prestamo = $this->uno(
            'SELECT p.*, u.nombre AS usuario, u.email AS usuario_email, u.rol AS usuario_rol,
                    l.titulo AS libro, l.isbn AS libro_isbn, l.autor AS libro_autor,
                    DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_retraso
               FROM prestamos p
               INNER JOIN usuarios u ON u.id = p.id_usuario
               INNER JOIN libros l ON l.id = p.id_libro
              WHERE p.id = :id',
            [':id' => $id]
        );

        if ($prestamo !== null) {
            $prestamo['multa'] = (new Multa())->porPrestamo($id);
        }

        return $prestamo;
    }

    /** Préstamos activos o vencidos de un usuario concreto. */
    public function activosDeUsuario(int $idUsuario): array
    {
        return $this->buscar(['id_usuario' => $idUsuario]);
    }

    /** Cantidad de préstamos sin devolver de un usuario. */
    public function contarActivos(int $idUsuario): int
    {
        return (int) $this->escalar(
            'SELECT COUNT(*) FROM prestamos
              WHERE id_usuario = :id AND estado IN ("activo", "vencido")',
            [':id' => $idUsuario]
        );
    }

    /** Indica si el usuario ya tiene ese libro sin devolver. */
    public function tieneActivo(int $idUsuario, int $idLibro): bool
    {
        return (int) $this->escalar(
            'SELECT COUNT(*) FROM prestamos
              WHERE id_usuario = :id_usuario AND id_libro = :id_libro
                AND estado IN ("activo", "vencido")',
            [':id_usuario' => $idUsuario, ':id_libro' => $idLibro]
        ) > 0;
    }

    /**
     * Registra un préstamo descontando un ejemplar del stock.
     *
     * @param array{id_usuario: int, id_libro: int, id_reserva: int|null,
     *              fecha_prestamo: string, fecha_devolucion_esperada: string} $datos
     * @return array{ok: bool, mensaje: string, id: int|null}
     */
    public function registrar(array $datos): array
    {
        $libro = (new Libro())->obtener((int) $datos['id_libro']);

        if ($libro === null) {
            return ['ok' => false, 'mensaje' => 'El libro indicado no existe.', 'id' => null];
        }

        $usuario = (new Usuario())->obtener((int) $datos['id_usuario']);

        if ($usuario === null) {
            return ['ok' => false, 'mensaje' => 'El usuario indicado no existe.', 'id' => null];
        }

        if ($usuario['estado'] !== 'activo') {
            return ['ok' => false, 'mensaje' => 'El usuario está bloqueado y no puede recibir préstamos.', 'id' => null];
        }

        // Reglas de negocio adicionales a la disponibilidad de stock.
        if ($this->tieneActivo((int) $datos['id_usuario'], (int) $datos['id_libro'])) {
            return [
                'ok'      => false,
                'mensaje' => 'El usuario ya tiene un ejemplar de este libro sin devolver.',
                'id'      => null,
            ];
        }

        $maximo = (int) config('prestamos.max_activos_por_estudiante', 3);

        if ($usuario['rol'] === 'estudiante' && $this->contarActivos((int) $datos['id_usuario']) >= $maximo) {
            return [
                'ok'      => false,
                'mensaje' => 'El estudiante alcanzó el límite de ' . $maximo . ' préstamos simultáneos.',
                'id'      => null,
            ];
        }

        try {
            $this->db->beginTransaction();

            // Descuento atómico: sólo tiene efecto si aún quedan ejemplares.
            $descontado = $this->ejecutar(
                'UPDATE libros
                    SET stock_disponible = stock_disponible - 1
                  WHERE id = :id AND stock_disponible > 0',
                [':id' => (int) $datos['id_libro']]
            );

            if ($descontado !== 1) {
                $this->db->rollBack();

                return [
                    'ok'      => false,
                    'mensaje' => 'No hay ejemplares disponibles de «' . $libro['titulo'] . '».',
                    'id'      => null,
                ];
            }

            $this->ejecutar(
                'INSERT INTO prestamos
                    (id_usuario, id_libro, id_reserva, fecha_prestamo,
                     fecha_devolucion_esperada, estado)
                 VALUES
                    (:id_usuario, :id_libro, :id_reserva, :fecha_prestamo,
                     :fecha_devolucion_esperada, "activo")',
                [
                    ':id_usuario'                => (int) $datos['id_usuario'],
                    ':id_libro'                  => (int) $datos['id_libro'],
                    ':id_reserva'                => $datos['id_reserva'] !== null ? (int) $datos['id_reserva'] : null,
                    ':fecha_prestamo'            => $datos['fecha_prestamo'],
                    ':fecha_devolucion_esperada' => $datos['fecha_devolucion_esperada'],
                ]
            );

            $id = $this->ultimoId();

            // La reserva de origen queda confirmada al entregarse el ejemplar.
            if ($datos['id_reserva'] !== null) {
                $this->ejecutar(
                    'UPDATE reservas SET estado = "confirmada" WHERE id = :id AND estado = "pendiente"',
                    [':id' => (int) $datos['id_reserva']]
                );
            }

            $this->db->commit();

            return [
                'ok'      => true,
                'mensaje' => 'Préstamo registrado. Stock disponible de «' . $libro['titulo'] . '»: '
                    . ((int) $libro['stock_disponible'] - 1) . '.',
                'id'      => $id,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[BiblioTech] Error al registrar préstamo: ' . $e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo registrar el préstamo. Intenta nuevamente.', 'id' => null];
        }
    }

    /**
     * Registra la devolución: devuelve el ejemplar al stock y genera la multa
     * si el préstamo estaba vencido.
     *
     * @return array{ok: bool, mensaje: string}
     */
    public function registrarDevolucion(int $id, ?string $fecha = null): array
    {
        $prestamo = $this->obtener($id);

        if ($prestamo === null) {
            return ['ok' => false, 'mensaje' => 'El préstamo indicado no existe.'];
        }

        if (in_array($prestamo['estado'], ['devuelto', 'cancelado'], true)) {
            return ['ok' => false, 'mensaje' => 'Ese préstamo ya fue cerrado anteriormente.'];
        }

        $fecha = $fecha ?? date('Y-m-d');

        // Días de retraso respecto a la fecha esperada.
        $esperada = strtotime((string) $prestamo['fecha_devolucion_esperada']);
        $real     = strtotime($fecha);
        $diasRetraso = ($esperada !== false && $real !== false && $real > $esperada)
            ? (int) floor(($real - $esperada) / 86400)
            : 0;

        try {
            $this->db->beginTransaction();

            $this->ejecutar(
                'UPDATE prestamos
                    SET fecha_devolucion_real = :fecha, estado = "devuelto"
                  WHERE id = :id AND estado IN ("activo", "vencido")',
                [':fecha' => $fecha, ':id' => $id]
            );

            // El ejemplar vuelve al stock sin superar nunca el stock total.
            $this->ejecutar(
                'UPDATE libros
                    SET stock_disponible = LEAST(stock_disponible + 1, stock_total)
                  WHERE id = :id',
                [':id' => (int) $prestamo['id_libro']]
            );

            $multa = (new Multa())->generarPorRetraso($id, $diasRetraso);

            $this->db->commit();

            $mensaje = 'Devolución registrada. El ejemplar volvió al stock disponible.';

            if ($multa !== null) {
                $mensaje .= ' Se generó una multa de ' . dinero($multa) . ' por ' . $diasRetraso . ' día(s) de retraso.';
            }

            return ['ok' => true, 'mensaje' => $mensaje];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[BiblioTech] Error al registrar devolución: ' . $e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo registrar la devolución. Intenta nuevamente.'];
        }
    }

    /**
     * Marca como vencidos los préstamos activos cuya fecha esperada ya pasó.
     * Es idempotente: puede ejecutarse en cada carga sin efectos secundarios.
     */
    public function actualizarVencidos(): int
    {
        return $this->ejecutar(
            'UPDATE prestamos
                SET estado = "vencido"
              WHERE estado = "activo" AND fecha_devolucion_esperada < CURDATE()'
        );
    }

    /**
     * Resumen para paneles: activos, vencidos y devueltos.
     *
     * @return array{total: int, activos: int, vencidos: int, devueltos: int, del_mes: int}
     */
    public function resumen(?int $idUsuario = null): array
    {
        $condicion = $idUsuario !== null ? ' WHERE id_usuario = :id' : '';
        $parametros = $idUsuario !== null ? [':id' => $idUsuario] : [];

        $fila = $this->uno(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(estado IN ("activo", "vencido")), 0) AS activos,
                    COALESCE(SUM(estado = "vencido"
                                 OR (estado = "activo" AND fecha_devolucion_esperada < CURDATE())), 0) AS vencidos,
                    COALESCE(SUM(estado = "devuelto"), 0) AS devueltos,
                    COALESCE(SUM(fecha_prestamo >= :inicio_mes), 0) AS del_mes
               FROM prestamos'
            . $condicion,
            $parametros + [':inicio_mes' => date('Y-m-01')]
        ) ?? [];

        return [
            'total'     => (int) ($fila['total'] ?? 0),
            'activos'   => (int) ($fila['activos'] ?? 0),
            'vencidos'  => (int) ($fila['vencidos'] ?? 0),
            'devueltos' => (int) ($fila['devueltos'] ?? 0),
            'del_mes'   => (int) ($fila['del_mes'] ?? 0),
        ];
    }
}
