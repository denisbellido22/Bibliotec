<?php
/**
 * BiblioTech — Plantilla de configuración PRIVADA de la base de datos.
 *
 * Cómo usarla:
 *   1. Copia este archivo como `config/database.local.php`.
 *   2. Escribe tus credenciales reales.
 *   3. `database.local.php` está en .gitignore: nunca se sube al repositorio.
 *
 * Solo se sobreescriben las claves que definas aquí; el resto se toma de
 * `config/config.php`.
 *
 * @return array<string, string>
 */

declare(strict_types=1);

return [
    'host'     => '127.0.0.1',
    'puerto'   => '3306',
    'nombre'   => 'Bibliotec',
    'usuario'  => 'USUARIO_DE_LA_BASE_DE_DATOS',
    'password' => 'CONTRASENA_DE_LA_BASE_DE_DATOS',
];
