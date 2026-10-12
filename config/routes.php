<?php
/**
 * BiblioTech — Tabla de rutas.
 *
 * Formato de cada entrada:
 *
 *   'nombre/de/la/ruta' => ['Controlador', 'metodo', $roles]
 *
 *   $roles = null                -> acceso público (catálogo, inicio de sesión).
 *   $roles = ['*']               -> requiere sesión, cualquier rol.
 *   $roles = ['administrador']   -> sólo ese rol (puede listar varios).
 *
 * El enrutador verifica este arreglo en el servidor antes de ejecutar el
 * controlador, así que escribir la URL directamente no salta el control.
 *
 * @return array<string, array{0: string, 1: string, 2: array<int, string>|null}>
 */

declare(strict_types=1);

return [

    /* ---------------- Público ---------------- */
    ''                      => ['HomeController', 'index', null],

    /* Catálogo: consultable sin sesión (RF04, RF05, RF06, RF07). */
    'catalogo'              => ['CatalogoController', 'index', null],
    'catalogo/ver'          => ['CatalogoController', 'ver', null],

    /* Acceso al sistema (RF01). */
    'login'                 => ['AuthController', 'login', null],
    'logout'                => ['AuthController', 'logout', ['*']],

    /* Panel del administrador (RF02). */
    'admin/dashboard'       => ['AdminController', 'dashboard', ['administrador']],

    /* Gestión de libros (RF03, RF07). */
    'admin/libros'          => ['LibroController', 'index', ['administrador']],
    'admin/libros/crear'    => ['LibroController', 'crear', ['administrador']],
    'admin/libros/ver'      => ['LibroController', 'ver', ['administrador']],
    'admin/libros/editar'   => ['LibroController', 'editar', ['administrador']],
    'admin/libros/eliminar' => ['LibroController', 'eliminar', ['administrador']],

    /* Gestión de categorías (RF08). */
    'admin/categorias'          => ['CategoriaController', 'index', ['administrador']],
    'admin/categorias/crear'    => ['CategoriaController', 'crear', ['administrador']],
    'admin/categorias/editar'   => ['CategoriaController', 'editar', ['administrador']],
    'admin/categorias/eliminar' => ['CategoriaController', 'eliminar', ['administrador']],

    /* Gestión de usuarios (RF09, RF10, RF11). */
    'admin/usuarios'          => ['UsuarioController', 'index', ['administrador']],
    'admin/usuarios/ver'      => ['UsuarioController', 'ver', ['administrador']],
    'admin/usuarios/crear'    => ['UsuarioController', 'crear', ['administrador']],
    'admin/usuarios/editar'   => ['UsuarioController', 'editar', ['administrador']],
    'admin/usuarios/estado'   => ['UsuarioController', 'estado', ['administrador']],
    'admin/usuarios/eliminar' => ['UsuarioController', 'eliminar', ['administrador']],

    /* Roles y permisos (RF12). */
    'admin/roles'          => ['RolController', 'index', ['administrador']],
    'admin/roles/asignar'  => ['RolController', 'asignar', ['administrador']],

    /*
     * Préstamos y devoluciones (RF13).
     * El mismo controlador y las mismas vistas sirven a ambos paneles: sólo
     * cambia el prefijo de la ruta y el rol autorizado.
     */
    'admin/prestamos'          => ['PrestamoController', 'index', ['administrador']],
    'admin/prestamos/crear'    => ['PrestamoController', 'crear', ['administrador']],
    'admin/prestamos/ver'      => ['PrestamoController', 'ver', ['administrador']],
    'admin/prestamos/devolver' => ['PrestamoController', 'devolver', ['administrador']],

    'bibliotecario/prestamos'          => ['PrestamoController', 'index', ['bibliotecario']],
    'bibliotecario/prestamos/crear'    => ['PrestamoController', 'crear', ['bibliotecario']],
    'bibliotecario/prestamos/ver'      => ['PrestamoController', 'ver', ['bibliotecario']],
    'bibliotecario/prestamos/devolver' => ['PrestamoController', 'devolver', ['bibliotecario']],

    /* Reservas: supervisión por el personal. */
    'admin/reservas'           => ['ReservaController', 'index', ['administrador']],
    'admin/reservas/confirmar' => ['ReservaController', 'confirmar', ['administrador']],
    'admin/reservas/cancelar'  => ['ReservaController', 'cancelar', ['administrador']],

    'bibliotecario/reservas'           => ['ReservaController', 'index', ['bibliotecario']],
    'bibliotecario/reservas/confirmar' => ['ReservaController', 'confirmar', ['bibliotecario']],
    'bibliotecario/reservas/cancelar'  => ['ReservaController', 'cancelar', ['bibliotecario']],

    /* Reportes y estadísticas (RF02). */
    'admin/reportes' => ['ReporteController', 'index', ['administrador']],

    /* Panel del bibliotecario (RF04, RF07). */
    'bibliotecario/panel'  => ['BibliotecarioController', 'panel', ['bibliotecario']],
    'bibliotecario/libros' => ['BibliotecarioController', 'libros', ['bibliotecario']],

    /* Multas: el personal registra pagos y condonaciones. */
    'admin/multas'          => ['MultaController', 'index', ['administrador']],
    'admin/multas/pagar'    => ['MultaController', 'pagar', ['administrador']],
    'admin/multas/condonar' => ['MultaController', 'condonar', ['administrador']],

    'bibliotecario/multas'          => ['MultaController', 'index', ['bibliotecario']],
    'bibliotecario/multas/pagar'    => ['MultaController', 'pagar', ['bibliotecario']],
    'bibliotecario/multas/condonar' => ['MultaController', 'condonar', ['bibliotecario']],

    /* Área personal del estudiante. */
    'estudiante/panel'     => ['EstudianteController', 'panel', ['estudiante']],
    'estudiante/prestamos' => ['EstudianteController', 'prestamos', ['estudiante']],

    /* Consulta de multas propias del estudiante. */
    'estudiante/multas' => ['MultaController', 'misMultas', ['estudiante']],

    /* Reservas propias del estudiante (control de acceso por registro). */
    'estudiante/reservas'          => ['ReservaController', 'misReservas', ['estudiante']],
    'estudiante/reservas/crear'    => ['ReservaController', 'reservar', ['estudiante']],
    'estudiante/reservas/cancelar' => ['ReservaController', 'cancelarPropia', ['estudiante']],

];
