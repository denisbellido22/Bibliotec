<?php
/**
 * BiblioTech — Controlador de la página principal.
 */

declare(strict_types=1);

class HomeController extends Controller
{
    /** Portada pública del sistema. */
    public function index(): void
    {
        render('home.index', [
            'titulo'  => 'Inicio',
            'usuario' => usuario_actual(),
        ]);
    }
}
