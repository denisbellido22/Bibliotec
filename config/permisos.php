<?php
/**
 * BiblioTech — Matriz de permisos por rol (RF12, RNF03).
 *
 * Es la referencia documental de los accesos permitidos. La verificación real
 * se ejecuta en config/routes.php, cuyo arreglo `$roles` debe mantenerse
 * coherente con esta matriz.
 *
 * @return array<string, array{descripcion: string, roles: array<int, string>, rf: string}>
 */

declare(strict_types=1);

return [
    'Acceso al sistema' => [
        'descripcion' => 'Iniciar y cerrar sesión con las credenciales de cada usuario.',
        'roles'       => ['administrador', 'bibliotecario', 'estudiante'],
        'rf'          => 'RF01',
    ],
    'Catálogo de libros' => [
        'descripcion' => 'Consultar el catálogo, buscar por título, autor o ISBN y filtrar por categoría.',
        'roles'       => ['administrador', 'bibliotecario', 'estudiante'],
        'rf'          => 'RF04, RF05, RF06',
    ],
    'Control de stock' => [
        'descripcion' => 'Consultar la cantidad de ejemplares totales y disponibles de cada título.',
        'roles'       => ['administrador', 'bibliotecario', 'estudiante'],
        'rf'          => 'RF07',
    ],
    'Panel administrativo' => [
        'descripcion' => 'Acceder al panel principal de administración y a sus indicadores.',
        'roles'       => ['administrador'],
        'rf'          => 'RF02',
    ],
    'Gestión de libros' => [
        'descripcion' => 'Registrar, editar y eliminar libros, incluida la carga de portadas.',
        'roles'       => ['administrador'],
        'rf'          => 'RF03',
    ],
    'Gestión de categorías' => [
        'descripcion' => 'Crear, editar y eliminar las categorías del catálogo.',
        'roles'       => ['administrador'],
        'rf'          => 'RF08',
    ],
    'Gestión de usuarios' => [
        'descripcion' => 'Registrar, buscar, filtrar, editar, activar, bloquear y eliminar usuarios.',
        'roles'       => ['administrador'],
        'rf'          => 'RF09, RF10, RF11',
    ],
    'Gestión de roles y permisos' => [
        'descripcion' => 'Asignar y modificar el rol de cada usuario del sistema.',
        'roles'       => ['administrador'],
        'rf'          => 'RF12',
    ],
    'Supervisión de préstamos' => [
        'descripcion' => 'Consultar, registrar y supervisar préstamos y devoluciones.',
        'roles'       => ['administrador', 'bibliotecario'],
        'rf'          => 'RF13',
    ],
    'Gestión de reservas' => [
        'descripcion' => 'Consultar y gestionar las reservas de ejemplares.',
        'roles'       => ['administrador', 'bibliotecario'],
        'rf'          => 'Reservas',
    ],
    'Gestión de multas' => [
        'descripcion' => 'Consultar multas, registrar pagos y condonaciones.',
        'roles'       => ['administrador', 'bibliotecario'],
        'rf'          => 'Multas',
    ],
    'Reportes y estadísticas' => [
        'descripcion' => 'Consultar indicadores y reportes del sistema.',
        'roles'       => ['administrador', 'bibliotecario'],
        'rf'          => 'RF02',
    ],
    'Mis préstamos, reservas y multas' => [
        'descripcion' => 'Consultar únicamente la información personal del estudiante autenticado.',
        'roles'       => ['estudiante'],
        'rf'          => 'Estudiante',
    ],
    'Realizar reservas' => [
        'descripcion' => 'Reservar ejemplares y cancelar las reservas propias.',
        'roles'       => ['estudiante'],
        'rf'          => 'Estudiante',
    ],
    'Registro de usuarios' => [
        'descripcion' => 'Crear una cuenta propia desde el formulario de registro público.',
        'roles'       => ['estudiante'],
        'rf'          => 'Estudiante',
    ],
];
