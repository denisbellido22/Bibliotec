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

];
