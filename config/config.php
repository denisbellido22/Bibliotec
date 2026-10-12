<?php
/**
 * BiblioTech — Configuración general del sistema.
 *
 * Este archivo NO contiene credenciales privadas versionadas: los valores por
 * defecto corresponden a la instalación estándar de XAMPP y pueden
 * sobreescribirse con variables de entorno o con `config/database.local.php`
 * (ignorado por Git).
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Zona horaria institucional (Perú) para fechas de préstamos, reservas y multas.
date_default_timezone_set('America/Lima');

return [
    'app' => [
        'nombre'    => 'BiblioTech',
        'subtitulo' => 'Sistema de Biblioteca Virtual',
        'version'   => '1.0.0',
    ],

    /*
     * Conexión a la base de datos YA EXISTENTE.
     * El nombre debe permanecer como `Bibliotec`: no se crea otra base de datos.
     */
    'db' => [
        'host'     => getenv('BIBLIOTECH_DB_HOST') ?: '127.0.0.1',
        'puerto'   => getenv('BIBLIOTECH_DB_PORT') ?: '3306',
        'nombre'   => getenv('BIBLIOTECH_DB_NAME') ?: 'Bibliotec',
        'usuario'  => getenv('BIBLIOTECH_DB_USER') ?: 'root',
        'password' => (string) (getenv('BIBLIOTECH_DB_PASS') ?: ''),
        'charset'  => 'utf8mb4',
    ],

    /*
     * Portadas de libros. La imagen se selecciona desde el explorador de
     * archivos; el servidor valida tipo real y tamaño antes de guardarla.
     */
    'uploads' => [
        'ruta'     => BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portadas',
        'carpeta'  => 'uploads/portadas',
        'max_bytes' => 2 * 1024 * 1024, // 2 MB
        'mimes'    => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ],
    ],

    /*
     * Reglas de negocio de la biblioteca (RF07, RF13 y módulos asociados).
     */
    'prestamos' => [
        'dias_prestamo'              => 7,     // Duración estándar del préstamo.
        'max_activos_por_estudiante' => 3,     // Límite de préstamos simultáneos.
        'multa_por_dia'              => 1.50,  // Soles por día de retraso.
        'dias_gracia'                => 0,     // Días sin multa tras el vencimiento.
    ],

    'reservas' => [
        'dias_vigencia'  => 3,  // Días antes de que una reserva expire.
        'max_activas'    => 3,  // Reservas simultáneas por estudiante.
    ],
];
