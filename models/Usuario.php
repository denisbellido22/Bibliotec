<?php
/**
 * BiblioTech — Modelo de usuarios (RF09, RF10, RF11, RF12).
 */

declare(strict_types=1);

class Usuario extends Model
{
    protected string $tabla = 'usuarios';

    /** Roles válidos, según el ENUM de la tabla `usuarios`. */
    public const ROLES = ['administrador', 'bibliotecario', 'estudiante'];

    /** Estados válidos, según el ENUM de la tabla `usuarios`. */
    public const ESTADOS = ['activo', 'bloqueado'];

    /**
     * Busca un usuario por su correo (incluye usuarios bloqueados).
     *
     * @return array<string, mixed>|null
     */
    public function porEmail(string $email): ?array
    {
        return $this->uno(
            'SELECT id, nombre, email, password_hash, rol, estado
               FROM usuarios
              WHERE email = :email
              LIMIT 1',
            [':email' => $email]
        );
    }

    /**
     * Un usuario por identificador.
     *
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT id, nombre, email, rol, estado, created_at, updated_at
               FROM usuarios
              WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * Listado con búsqueda por nombre o correo y filtros por rol y estado
     * (RF09, RF10, RF11).
     *
     * @param array{texto?: string, rol?: string, estado?: string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function buscar(array $filtros = []): array
    {
        $condiciones = [];
        $parametros  = [];

        $texto = trim((string) ($filtros['texto'] ?? ''));
        if ($texto !== '') {
            $condiciones[] = '(nombre LIKE :texto_nombre OR email LIKE :texto_email)';
            $comodin = '%' . $texto . '%';
            $parametros[':texto_nombre'] = $comodin;
            $parametros[':texto_email']  = $comodin;
        }

        $rol = (string) ($filtros['rol'] ?? '');
        if ($rol !== '' && in_array($rol, self::ROLES, true)) {
            $condiciones[] = 'rol = :rol';
            $parametros[':rol'] = $rol;
        }

        $estado = (string) ($filtros['estado'] ?? '');
        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $condiciones[] = 'estado = :estado';
            $parametros[':estado'] = $estado;
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        return $this->consultar(
            'SELECT u.id, u.nombre, u.email, u.rol, u.estado, u.created_at,
                    (SELECT COUNT(*) FROM prestamos p WHERE p.id_usuario = u.id) AS total_prestamos
               FROM usuarios u'
            . $where
            . ' ORDER BY u.nombre ASC',
            $parametros
        );
    }

    /**
     * Totales por rol y estado (RF02, RF11).
     *
     * @return array{total: int, estudiantes: int, bibliotecarios: int, administradores: int, activos: int, bloqueados: int}
     */
    public function resumen(): array
    {
        $fila = $this->uno(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(rol = "estudiante"), 0)     AS estudiantes,
                    COALESCE(SUM(rol = "bibliotecario"), 0)  AS bibliotecarios,
                    COALESCE(SUM(rol = "administrador"), 0)  AS administradores,
                    COALESCE(SUM(estado = "activo"), 0)      AS activos,
                    COALESCE(SUM(estado = "bloqueado"), 0)   AS bloqueados
               FROM usuarios'
        ) ?? [];

        return [
            'total'          => (int) ($fila['total'] ?? 0),
            'estudiantes'    => (int) ($fila['estudiantes'] ?? 0),
            'bibliotecarios' => (int) ($fila['bibliotecarios'] ?? 0),
            'administradores' => (int) ($fila['administradores'] ?? 0),
            'activos'        => (int) ($fila['activos'] ?? 0),
            'bloqueados'     => (int) ($fila['bloqueados'] ?? 0),
        ];
    }

    /** Indica si el correo ya está registrado por otro usuario. */
    public function emailRegistrado(string $email, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $parametros = [':email' => $email];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros[':id'] = $exceptoId;
        }

        return (int) $this->escalar($sql, $parametros) > 0;
    }

    /**
     * Crea un usuario. La contraseña se recibe en texto plano y se guarda
     * siempre con password_hash() (RNF02).
     */
    public function crear(string $nombre, string $email, string $password, string $rol, string $estado): int
    {
        $this->ejecutar(
            'INSERT INTO usuarios (nombre, email, password_hash, rol, estado)
             VALUES (:nombre, :email, :hash, :rol, :estado)',
            [
                ':nombre' => $nombre,
                ':email'  => $email,
                ':hash'   => password_hash($password, PASSWORD_DEFAULT),
                ':rol'    => $rol,
                ':estado' => $estado,
            ]
        );

        return $this->ultimoId();
    }

    /**
     * Actualiza los datos de un usuario.
     *
     * Cuando $password es null se conserva el hash actual, de modo que editar
     * el perfil no obliga a cambiar la contraseña.
     */
    public function actualizar(int $id, string $nombre, string $email, string $rol, string $estado, ?string $password = null): void
    {
        $parametros = [
            ':nombre' => $nombre,
            ':email'  => $email,
            ':rol'    => $rol,
            ':estado' => $estado,
            ':id'     => $id,
        ];

        $sqlHash = '';

        if ($password !== null && $password !== '') {
            $sqlHash = ', password_hash = :hash';
            $parametros[':hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->ejecutar(
            'UPDATE usuarios
                SET nombre = :nombre,
                    email = :email,
                    rol = :rol,
                    estado = :estado'
            . $sqlHash .
            ' WHERE id = :id',
            $parametros
        );
    }

    /** Cambia únicamente el estado del usuario (activar / desactivar). */
    public function cambiarEstado(int $id, string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            return;
        }

        $this->ejecutar('UPDATE usuarios SET estado = :estado WHERE id = :id', [
            ':estado' => $estado,
            ':id'     => $id,
        ]);
    }

    /** Cambia únicamente el rol del usuario (RF12). */
    public function cambiarRol(int $id, string $rol): bool
    {
        if (!in_array($rol, self::ROLES, true)) {
            return false;
        }

        $this->ejecutar('UPDATE usuarios SET rol = :rol WHERE id = :id', [
            ':rol' => $rol,
            ':id'  => $id,
        ]);

        return true;
    }

    /** Cantidad de usuarios activos con un rol concreto. */
    public function contarPorRol(string $rol, string $estado = 'activo'): int
    {
        return (int) $this->escalar(
            'SELECT COUNT(*) FROM usuarios WHERE rol = :rol AND estado = :estado',
            [':rol' => $rol, ':estado' => $estado]
        );
    }

    /**
     * Movimientos vinculados a un usuario, usados para impedir borrados que
     * dejarían el historial inconsistente (RNF07).
     *
     * @return array<int, string>
     */
    public function motivosBloqueo(int $id): array
    {
        $prestamos = (int) $this->escalar('SELECT COUNT(*) FROM prestamos WHERE id_usuario = :id', [':id' => $id]);
        $reservas  = (int) $this->escalar('SELECT COUNT(*) FROM reservas WHERE id_usuario = :id', [':id' => $id]);

        $motivos = [];

        if ($prestamos > 0) {
            $motivos[] = $prestamos . ' préstamo(s) registrado(s)';
        }

        if ($reservas > 0) {
            $motivos[] = $reservas . ' reserva(s) registrada(s)';
        }

        return $motivos;
    }

    /**
     * Elimina un usuario sin movimientos asociados.
     * Devuelve false cuando el historial lo impide.
     */
    public function eliminar(int $id): bool
    {
        if ($this->motivosBloqueo($id) !== []) {
            return false;
        }

        $this->ejecutar('DELETE FROM usuarios WHERE id = :id', [':id' => $id]);

        return true;
    }
}
