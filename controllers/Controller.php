<?php
/**
 * BiblioTech — Controlador base.
 *
 * Reúne utilidades compartidas por todos los módulos: renderizado de vistas,
 * lectura de datos del usuario autenticado y respuestas JSON.
 */

declare(strict_types=1);

abstract class Controller
{
    /**
     * Renderiza una vista dentro de la plantilla indicada.
     *
     * @param array<string, mixed> $datos
     */
    protected function vista(string $vista, array $datos = [], ?string $layout = 'layouts.app'): void
    {
        render($vista, $datos, $layout);
    }

    /**
     * Datos del usuario autenticado (o null).
     *
     * @return array<string, mixed>|null
     */
    protected function usuario(): ?array
    {
        return usuario_actual();
    }

    /**
     * Exige que la sesión pertenezca a uno de los roles indicados.
     */
    protected function exigirRol(string ...$roles): void
    {
        requerir_rol(...$roles);
    }

    /**
     * Responde con JSON y termina la ejecución.
     *
     * @param array<string, mixed> $datos
     */
    protected function json(array $datos, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
