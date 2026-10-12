<?php
/**
 * BiblioTech — Controlador de la página principal.
 *
 * La portada muestra información obtenida en tiempo real de la base de datos
 * `Bibliotec`: totales del catálogo, títulos recién incorporados y las
 * categorías con ejemplares disponibles.
 */

declare(strict_types=1);

class HomeController extends Controller
{
    /** Portada pública del sistema. */
    public function index(): void
    {
        $libros     = new Libro();
        $categorias = new Categoria();

        render('home.index', [
            'titulo'     => 'Inicio',
            'usuario'    => $this->usuario(),
            'resumen'    => $libros->resumenStock(),
            'destacados' => $libros->destacados(4),
            'categorias' => $categorias->conDisponibles(),
        ]);
    }
}
