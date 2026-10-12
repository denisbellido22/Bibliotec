<?php
/**
 * BiblioTech — Inicio y cierre de sesión (RF01, RNF02).
 */

declare(strict_types=1);

class AuthController extends Controller
{
    /** Formulario y procesamiento del inicio de sesión. */
    public function login(): void
    {
        // Si ya hay sesión, se envía al panel que corresponde a su rol.
        if (esta_autenticado()) {
            redirigir($this->destinoPorRol());
        }

        $errores = [];
        $datos   = ['email' => ''];

        if (es_post()) {
            $datos['email'] = (string) entrada('email', '');
            $password       = (string) ($_POST['password'] ?? '');

            // Validación de entradas antes de tocar la base de datos.
            if ($datos['email'] === '') {
                $errores['email'] = 'Ingresa tu correo electrónico.';
            } elseif (filter_var($datos['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errores['email'] = 'El correo electrónico no tiene un formato válido.';
            }

            if ($password === '') {
                $errores['password'] = 'Ingresa tu contraseña.';
            }

            if ($errores === []) {
                $usuario = autenticar($datos['email'], $password);

                if ($usuario !== null) {
                    iniciar_sesion($usuario);
                    flash('exito', '¡Bienvenido, ' . $usuario['nombre'] . '!');
                    redirigir($this->destinoSolicitado());
                }

                // Se distingue una cuenta bloqueada de credenciales incorrectas.
                $existente = (new Usuario())->porEmail($datos['email']);

                if ($existente !== null && $existente['estado'] === 'bloqueado') {
                    $errores['general'] = 'Tu cuenta está bloqueada. Contacta al administrador del sistema.';
                } else {
                    $errores['general'] = 'El correo o la contraseña son incorrectos.';
                }
            }
        }

        render('auth.login', [
            'titulo'  => 'Iniciar sesión',
            'errores' => $errores,
            'datos'   => $datos,
        ], 'layouts.acceso');
    }

    /** Cierra la sesión y vuelve al formulario de acceso. */
    public function logout(): void
    {
        cerrar_sesion();
        flash('exito', 'Tu sesión se cerró correctamente.');

        redirigir('login');
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /** Destino solicitado antes de autenticarse, si es una ruta válida. */
    private function destinoSolicitado(): string
    {
        $destino = (string) entrada('destino', '');

        if ($destino === '') {
            return $this->destinoPorRol();
        }

        $rutas = require BASE_PATH . '/config/routes.php';

        return array_key_exists($destino, $rutas) ? $destino : $this->destinoPorRol();
    }

    /** Panel inicial que corresponde al rol autenticado. */
    private function destinoPorRol(): string
    {
        $candidato = match (rol_actual()) {
            'administrador' => 'admin/dashboard',
            'bibliotecario' => 'bibliotecario/panel',
            'estudiante'    => 'estudiante/panel',
            default         => 'catalogo',
        };

        // Si el módulo todavía no está disponible, se usa el catálogo.
        $rutas = require BASE_PATH . '/config/routes.php';

        return array_key_exists($candidato, $rutas) ? $candidato : 'catalogo';
    }
}
