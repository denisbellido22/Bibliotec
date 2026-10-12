<?php
/**
 * BiblioTech — Roles y permisos (RF12).
 *
 * Muestra la matriz de permisos por rol y permite reasignar el rol de un
 * usuario, protegiendo siempre la existencia de al menos un administrador.
 */

declare(strict_types=1);

class RolController extends Controller
{
    private Usuario $usuarios;

    public function __construct()
    {
        $this->usuarios = new Usuario();
    }

    /** Matriz de permisos y asignación de roles. */
    public function index(): void
    {
        $this->exigirRol('administrador');

        $matriz = require BASE_PATH . '/config/permisos.php';

        $conteo = [];
        foreach (Usuario::ROLES as $rol) {
            $conteo[$rol] = [
                'activos'    => $this->usuarios->contarPorRol($rol, 'activo'),
                'bloqueados' => $this->usuarios->contarPorRol($rol, 'bloqueado'),
            ];
        }

        $this->vista('admin.roles.index', [
            'titulo'     => 'Roles y permisos',
            'conSidebar' => true,
            'matriz'     => $matriz,
            'conteo'     => $conteo,
            'usuarios'   => $this->usuarios->buscar([]),
        ]);
    }

    /** Cambia el rol de un usuario. */
    public function asignar(): void
    {
        $this->exigirRol('administrador');

        $id      = entrada_int('id');
        $usuario = $this->usuarios->obtener($id);

        if ($usuario === null) {
            flash('error', 'El usuario solicitado no existe.');
            redirigir('admin/roles');
        }

        $nuevoRol = (string) entrada('rol', '');

        if (!in_array($nuevoRol, Usuario::ROLES, true)) {
            flash('error', 'El rol seleccionado no es válido.');
            redirigir('admin/roles');
        }

        // Integridad del control de acceso: no quedarse sin administradores.
        if ($usuario['rol'] === 'administrador' && $nuevoRol !== 'administrador') {
            if ($this->usuarios->contarPorRol('administrador') <= 1) {
                flash('error', 'Debe existir al menos un administrador activo en el sistema.');
                redirigir('admin/roles');
            }
        }

        if ($id === id_usuario_actual() && $nuevoRol !== 'administrador') {
            flash('error', 'No puedes quitarte a ti mismo el rol de administrador.');
            redirigir('admin/roles');
        }

        $this->usuarios->cambiarRol($id, $nuevoRol);

        flash('exito', $usuario['nombre'] . ' ahora tiene el rol de ' . nombre_rol($nuevoRol) . '.');
        redirigir('admin/roles');
    }
}
