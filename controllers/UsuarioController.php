<?php
/**
 * BiblioTech — Gestión de usuarios (RF09, RF10, RF11).
 *
 * Incluye búsqueda por nombre o correo, filtrado por rol y estado, alta,
 * edición, activación, desactivación y eliminación protegida.
 */

declare(strict_types=1);

class UsuarioController extends Controller
{
    private Usuario $usuarios;

    public function __construct()
    {
        $this->usuarios = new Usuario();
    }

    /** Listado con búsqueda y filtros. */
    public function index(): void
    {
        $this->exigirRol('administrador');

        $filtros = [
            'texto'  => (string) entrada('q', ''),
            'rol'    => in_array(entrada('rol', ''), Usuario::ROLES, true) ? (string) entrada('rol', '') : '',
            'estado' => in_array(entrada('estado', ''), Usuario::ESTADOS, true) ? (string) entrada('estado', '') : '',
        ];

        $this->vista('admin.usuarios.index', [
            'titulo'     => 'Usuarios',
            'conSidebar' => true,
            'usuarios'   => $this->usuarios->buscar($filtros),
            'filtros'    => $filtros,
            'resumen'    => $this->usuarios->resumen(),
        ]);
    }

    /** Ficha de un usuario. */
    public function ver(): void
    {
        $this->exigirRol('administrador');

        $usuario = $this->usuarios->obtener(entrada_int('id'));

        if ($usuario === null) {
            flash('error', 'El usuario solicitado no existe.');
            redirigir('admin/usuarios');
        }

        $this->vista('admin.usuarios.ver', [
            'titulo'     => (string) $usuario['nombre'],
            'conSidebar' => true,
            'usuario'    => $usuario,
            'motivos'    => $this->usuarios->motivosBloqueo((int) $usuario['id']),
        ]);
    }

    /** Alta de usuario con contraseña cifrada. */
    public function crear(): void
    {
        $this->exigirRol('administrador');

        $datos   = ['nombre' => '', 'email' => '', 'rol' => 'estudiante', 'estado' => 'activo'];
        $errores = [];

        if (es_post()) {
            $datos   = $this->datosDesdePeticion();
            $password = (string) ($_POST['password'] ?? '');
            $errores = $this->validar($datos, null, $password);

            if ($errores === []) {
                $this->usuarios->crear($datos['nombre'], $datos['email'], $password, $datos['rol'], $datos['estado']);

                flash('exito', 'El usuario «' . $datos['nombre'] . '» se registró correctamente.');
                redirigir('admin/usuarios');
            }
        }

        $this->vista('admin.usuarios.formulario', [
            'titulo'     => 'Registrar usuario',
            'conSidebar' => true,
            'accion'     => 'crear',
            'datos'      => $datos,
            'errores'    => $errores,
        ]);
    }

    /** Edición de usuario; la contraseña es opcional. */
    public function editar(): void
    {
        $this->exigirRol('administrador');

        $id      = entrada_int('id');
        $usuario = $this->usuarios->obtener($id);

        if ($usuario === null) {
            flash('error', 'El usuario solicitado no existe.');
            redirigir('admin/usuarios');
        }

        $datos = [
            'nombre' => (string) $usuario['nombre'],
            'email'  => (string) $usuario['email'],
            'rol'    => (string) $usuario['rol'],
            'estado' => (string) $usuario['estado'],
        ];
        $errores = [];

        if (es_post()) {
            $datos    = $this->datosDesdePeticion();
            $password = (string) ($_POST['password'] ?? '');

            $errores = $this->validar($datos, $id, $password, false);
            $errores = array_merge($errores, $this->protegerAdministracion($id, $datos));

            if ($errores === []) {
                $this->usuarios->actualizar(
                    $id,
                    $datos['nombre'],
                    $datos['email'],
                    $datos['rol'],
                    $datos['estado'],
                    $password !== '' ? $password : null
                );

                flash('exito', 'Los datos del usuario se actualizaron correctamente.');
                redirigir('admin/usuarios');
            }
        }

        $this->vista('admin.usuarios.formulario', [
            'titulo'     => 'Editar usuario',
            'conSidebar' => true,
            'accion'     => 'editar',
            'id'         => $id,
            'datos'      => $datos,
            'errores'    => $errores,
            'usuario'    => $usuario,
        ]);
    }

    /** Activa o bloquea la cuenta (RF09). */
    public function estado(): void
    {
        $this->exigirRol('administrador');

        $id      = entrada_int('id');
        $usuario = $this->usuarios->obtener($id);

        if ($usuario === null) {
            flash('error', 'El usuario solicitado no existe.');
            redirigir('admin/usuarios');
        }

        $nuevoEstado = $usuario['estado'] === 'activo' ? 'bloqueado' : 'activo';

        // No se permite quedarse sin administradores activos ni bloquearse a sí mismo.
        if ($nuevoEstado === 'bloqueado') {
            if ($id === id_usuario_actual()) {
                flash('error', 'No puedes bloquear tu propia cuenta.');
                redirigir('admin/usuarios');
            }

            if ($usuario['rol'] === 'administrador' && $this->usuarios->contarPorRol('administrador') <= 1) {
                flash('error', 'Debe existir al menos un administrador activo en el sistema.');
                redirigir('admin/usuarios');
            }
        }

        $this->usuarios->cambiarEstado($id, $nuevoEstado);

        flash(
            'exito',
            'La cuenta de ' . $usuario['nombre'] . ' quedó '
            . ($nuevoEstado === 'activo' ? 'activa' : 'bloqueada') . '.'
        );
        redirigir('admin/usuarios');
    }

    /** Eliminación protegida por historial. */
    public function eliminar(): void
    {
        $this->exigirRol('administrador');

        $id      = entrada_int('id');
        $usuario = $this->usuarios->obtener($id);

        if ($usuario === null) {
            flash('error', 'El usuario que intentas eliminar no existe.');
            redirigir('admin/usuarios');
        }

        if ($id === id_usuario_actual()) {
            flash('error', 'No puedes eliminar tu propia cuenta.');
            redirigir('admin/usuarios');
        }

        if ($usuario['rol'] === 'administrador' && $this->usuarios->contarPorRol('administrador') <= 1) {
            flash('error', 'Debe existir al menos un administrador activo en el sistema.');
            redirigir('admin/usuarios');
        }

        $motivos = $this->usuarios->motivosBloqueo($id);

        if ($motivos !== []) {
            flash(
                'error',
                'No se puede eliminar a ' . $usuario['nombre'] . ' porque tiene '
                . implode(' y ', $motivos) . '. Bloquea la cuenta en su lugar.'
            );
            redirigir('admin/usuarios');
        }

        $this->usuarios->eliminar($id);

        flash('exito', 'El usuario «' . $usuario['nombre'] . '» se eliminó correctamente.');
        redirigir('admin/usuarios');
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /** @return array{nombre: string, email: string, rol: string, estado: string} */
    private function datosDesdePeticion(): array
    {
        $rol    = (string) entrada('rol', 'estudiante');
        $estado = (string) entrada('estado', 'activo');

        return [
            'nombre' => (string) entrada('nombre', ''),
            'email'  => mb_strtolower((string) entrada('email', ''), 'UTF-8'),
            'rol'    => in_array($rol, Usuario::ROLES, true) ? $rol : 'estudiante',
            'estado' => in_array($estado, Usuario::ESTADOS, true) ? $estado : 'activo',
        ];
    }

    /**
     * @param array{nombre: string, email: string, rol: string, estado: string} $datos
     * @return array<string, string>
     */
    private function validar(array $datos, ?int $id, string $password, bool $passwordObligatoria = true): array
    {
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($datos['nombre'], 'UTF-8') > 120) {
            $errores['nombre'] = 'El nombre no puede superar los 120 caracteres.';
        }

        if ($datos['email'] === '') {
            $errores['email'] = 'El correo electrónico es obligatorio.';
        } elseif (filter_var($datos['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'] = 'El correo electrónico no tiene un formato válido.';
        } elseif (mb_strlen($datos['email'], 'UTF-8') > 180) {
            $errores['email'] = 'El correo no puede superar los 180 caracteres.';
        } elseif ($this->usuarios->emailRegistrado($datos['email'], $id)) {
            $errores['email'] = 'Ese correo ya está registrado por otro usuario.';
        }

        if ($passwordObligatoria && $password === '') {
            $errores['password'] = 'La contraseña es obligatoria al crear el usuario.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        return $errores;
    }

    /**
     * Impide dejar el sistema sin administradores activos ni modificar el
     * propio rol (RF12, integridad del control de acceso).
     *
     * @param array{rol: string, estado: string} $datos
     * @return array<string, string>
     */
    private function protegerAdministracion(int $id, array $datos): array
    {
        $errores = [];

        $usuario  = $this->usuarios->obtener($id);
        $eraAdmin = $usuario !== null && $usuario['rol'] === 'administrador';
        $sigueSiendoAdminActivo = $datos['rol'] === 'administrador' && $datos['estado'] === 'activo';

        if ($eraAdmin && !$sigueSiendoAdminActivo && $this->usuarios->contarPorRol('administrador') <= 1) {
            $errores['rol'] = 'Debe existir al menos un administrador activo en el sistema.';
        }

        if ($id === id_usuario_actual() && $datos['estado'] === 'bloqueado') {
            $errores['estado'] = 'No puedes bloquear tu propia cuenta.';
        }

        return $errores;
    }
}
