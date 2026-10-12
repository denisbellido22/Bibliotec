<?php
/**
 * BiblioTech — Gestión de categorías (RF08).
 */

declare(strict_types=1);

class CategoriaController extends Controller
{
    private Categoria $categorias;

    public function __construct()
    {
        $this->categorias = new Categoria();
    }

    /** Listado de categorías con la cantidad de libros clasificados. */
    public function index(): void
    {
        $this->exigirRol('administrador');

        $this->vista('admin.categorias.index', [
            'titulo'     => 'Categorías',
            'conSidebar' => true,
            'categorias' => $this->categorias->conConteo(),
        ]);
    }

    /** Formulario y guardado de una categoría nueva. */
    public function crear(): void
    {
        $this->exigirRol('administrador');

        $datos   = ['nombre' => '', 'descripcion' => ''];
        $errores = [];

        if (es_post()) {
            $datos   = $this->datosDesdePeticion();
            $errores = $this->validar($datos);

            if ($errores === []) {
                $this->categorias->crear($datos['nombre'], $datos['descripcion']);

                flash('exito', 'La categoría «' . $datos['nombre'] . '» se registró correctamente.');
                redirigir('admin/categorias');
            }
        }

        $this->vista('admin.categorias.formulario', [
            'titulo'     => 'Nueva categoría',
            'conSidebar' => true,
            'accion'     => 'crear',
            'datos'      => $datos,
            'errores'    => $errores,
        ]);
    }

    /** Formulario y guardado de una categoría existente. */
    public function editar(): void
    {
        $this->exigirRol('administrador');

        $id        = entrada_int('id');
        $categoria = $this->categorias->obtener($id);

        if ($categoria === null) {
            flash('error', 'La categoría solicitada no existe.');
            redirigir('admin/categorias');
        }

        $datos   = [
            'nombre'      => (string) $categoria['nombre'],
            'descripcion' => (string) ($categoria['descripcion'] ?? ''),
        ];
        $errores = [];

        if (es_post()) {
            $datos   = $this->datosDesdePeticion();
            $errores = $this->validar($datos, $id);

            if ($errores === []) {
                $this->categorias->actualizar($id, $datos['nombre'], $datos['descripcion']);

                flash('exito', 'La categoría se actualizó correctamente.');
                redirigir('admin/categorias');
            }
        }

        $this->vista('admin.categorias.formulario', [
            'titulo'     => 'Editar categoría',
            'conSidebar' => true,
            'accion'     => 'editar',
            'id'         => $id,
            'datos'      => $datos,
            'errores'    => $errores,
            'categoria'  => $categoria,
            'totalLibros' => $this->categorias->contarLibros($id),
        ]);
    }

    /** Elimina una categoría sin libros asociados. */
    public function eliminar(): void
    {
        $this->exigirRol('administrador');

        $id        = entrada_int('id');
        $categoria = $this->categorias->obtener($id);

        if ($categoria === null) {
            flash('error', 'La categoría que intentas eliminar no existe.');
            redirigir('admin/categorias');
        }

        $libros = $this->categorias->contarLibros($id);

        if ($libros > 0) {
            flash(
                'error',
                'No se puede eliminar «' . $categoria['nombre'] . '» porque tiene '
                . $libros . ' libro(s) clasificado(s). Reasígnalos primero.'
            );
            redirigir('admin/categorias');
        }

        $this->categorias->eliminar($id);

        flash('exito', 'La categoría «' . $categoria['nombre'] . '» se eliminó correctamente.');
        redirigir('admin/categorias');
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /** @return array{nombre: string, descripcion: string} */
    private function datosDesdePeticion(): array
    {
        return [
            'nombre'      => (string) entrada('nombre', ''),
            'descripcion' => (string) entrada('descripcion', ''),
        ];
    }

    /**
     * @param array{nombre: string, descripcion: string} $datos
     * @return array<string, string>
     */
    private function validar(array $datos, ?int $id = null): array
    {
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre de la categoría es obligatorio.';
        } elseif (mb_strlen($datos['nombre'], 'UTF-8') > 100) {
            $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
        } elseif ($this->categorias->nombreRegistrado($datos['nombre'], $id)) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }

        if (mb_strlen($datos['descripcion'], 'UTF-8') > 1000) {
            $errores['descripcion'] = 'La descripción es demasiado larga.';
        }

        return $errores;
    }
}
