<?php
/**
 * BiblioTech — Gestión de libros (RF03, RF07).
 *
 * Registrar, listar, editar y eliminar libros, con validación de campos
 * obligatorios, ISBN y stock, y carga de portada desde el explorador.
 */

declare(strict_types=1);

class LibroController extends Controller
{
    /** Registros por página del listado administrativo. */
    private const POR_PAGINA = 15;

    private Libro $libros;
    private Categoria $categorias;

    public function __construct()
    {
        $this->libros     = new Libro();
        $this->categorias = new Categoria();
    }

    /** Listado administrativo con búsqueda y filtros. */
    public function index(): void
    {
        $this->exigirRol('administrador');

        $filtros = [
            'texto'          => (string) entrada('q', ''),
            'id_categoria'   => max(0, entrada_int('categoria')),
            'disponibilidad' => in_array(entrada('disponibilidad', 'todos'), ['todos', 'disponibles', 'agotados'], true)
                ? (string) entrada('disponibilidad', 'todos')
                : 'todos',
            'orden'          => in_array(entrada('orden', 'titulo'), ['titulo', 'autor', 'reciente', 'anio'], true)
                ? (string) entrada('orden', 'titulo')
                : 'titulo',
        ];

        $resultado = $this->libros->buscar($filtros, max(1, entrada_int('pagina', 1)), self::POR_PAGINA);

        $this->vista('admin.libros.index', [
            'titulo'     => 'Libros',
            'conSidebar' => true,
            'resultado'  => $resultado,
            'filtros'    => $filtros,
            'categorias' => $this->categorias->todas(),
            'resumen'    => $this->libros->resumenStock(),
            'parametros' => $this->parametrosPaginacion($filtros),
        ]);
    }

    /** Formulario y guardado de un libro nuevo. */
    public function crear(): void
    {
        $this->exigirRol('administrador');

        $datos   = $this->datosVacios();
        $errores = [];
        $archivoSubido = null;

        if (es_post()) {
            $datos = $this->datosDesdePeticion();
            $errores = $this->validar($datos, null);

            $archivoSubido = guardar_portada($_FILES['portada'] ?? null, $errorArchivo);

            if ($errorArchivo !== null) {
                $errores['portada'] = $errorArchivo;
            }

            if ($errores === []) {
                $datos['portada_url'] = $archivoSubido ?? $datos['portada_url'];

                $nuevoId = $this->libros->crear($datos);

                flash('exito', 'Libro «' . $datos['titulo'] . '» registrado correctamente.');
                redirigir('admin/libros/ver', ['id' => $nuevoId]);
            }

            // El archivo ya guardado se descarta si el formulario no es válido.
            if ($archivoSubido !== null) {
                eliminar_portada_local($archivoSubido);
            }
        }

        $this->vista('admin.libros.formulario', [
            'titulo'     => 'Registrar libro',
            'conSidebar' => true,
            'accion'     => 'crear',
            'datos'      => $datos,
            'errores'    => $errores,
            'categorias' => $this->categorias->todas(),
        ]);
    }

    /** Formulario y guardado de un libro existente. */
    public function editar(): void
    {
        $this->exigirRol('administrador');

        $id    = entrada_int('id');
        $libro = $this->libros->obtener($id);

        if ($libro === null) {
            flash('error', 'El libro solicitado no existe.');
            redirigir('admin/libros');
        }

        $datos   = $this->datosDesdeLibro($libro);
        $errores = [];

        if (es_post()) {
            $datos      = $this->datosDesdePeticion();
            $errores    = $this->validar($datos, $id);
            $archivoSubido = guardar_portada($_FILES['portada'] ?? null, $errorArchivo);

            if ($errorArchivo !== null) {
                $errores['portada'] = $errorArchivo;
            }

            if ($errores === []) {
                if ($archivoSubido !== null) {
                    // Se reemplaza la portada: la anterior local se elimina.
                    eliminar_portada_local($libro['portada_url']);
                    $datos['portada_url'] = $archivoSubido;
                }

                $this->libros->actualizar($id, $datos);

                flash('exito', 'Los datos del libro se actualizaron correctamente.');
                redirigir('admin/libros/ver', ['id' => $id]);
            }

            if ($archivoSubido !== null) {
                eliminar_portada_local($archivoSubido);
            }
        }

        $this->vista('admin.libros.formulario', [
            'titulo'     => 'Editar libro',
            'conSidebar' => true,
            'accion'     => 'editar',
            'id'         => $id,
            'datos'      => $datos,
            'errores'    => $errores,
            'categorias' => $this->categorias->todas(),
            'libro'      => $libro,
        ]);
    }

    /** Ficha administrativa del libro. */
    public function ver(): void
    {
        $this->exigirRol('administrador');

        $id    = entrada_int('id');
        $libro = $this->libros->obtener($id);

        if ($libro === null) {
            flash('error', 'El libro solicitado no existe.');
            redirigir('admin/libros');
        }

        $this->vista('admin.libros.ver', [
            'titulo'     => (string) $libro['titulo'],
            'conSidebar' => true,
            'libro'      => $libro,
        ]);
    }

    /** Eliminación con confirmación previa desde el listado. */
    public function eliminar(): void
    {
        $this->exigirRol('administrador');

        $id    = entrada_int('id');
        $libro = $this->libros->obtener($id);

        if ($libro === null) {
            flash('error', 'El libro que intentas eliminar no existe.');
            redirigir('admin/libros');
        }

        $motivos = $this->libros->motivosBloqueo($id);

        if ($motivos !== []) {
            flash(
                'error',
                'No se puede eliminar «' . $libro['titulo'] . '» porque tiene '
                . implode(' y ', $motivos) . '. Conserva el historial del sistema.'
            );
            redirigir('admin/libros');
        }

        $this->libros->eliminar($id);
        eliminar_portada_local($libro['portada_url']);

        flash('exito', 'El libro «' . $libro['titulo'] . '» se eliminó correctamente.');
        redirigir('admin/libros');
    }

    /* ------------------------------------------------------------------
     * Apoyo interno
     * ------------------------------------------------------------------ */

    /**
     * Datos del formulario normalizados.
     *
     * @return array<string, mixed>
     */
    private function datosDesdePeticion(): array
    {
        $anio = entrada('anio', '');

        return [
            'titulo'           => (string) entrada('titulo', ''),
            'autor'            => (string) entrada('autor', ''),
            'isbn'             => (string) $this->normalizarIsbn((string) entrada('isbn', '')),
            'id_categoria'     => entrada_int('id_categoria') > 0 ? entrada_int('id_categoria') : null,
            'editorial'        => (string) entrada('editorial', ''),
            'anio'             => ($anio !== null && $anio !== '') ? (int) $anio : null,
            'portada_url'      => (string) entrada('portada_url', ''),
            'sinopsis'         => (string) entrada('sinopsis', ''),
            'stock_total'      => max(0, entrada_int('stock_total')),
            'stock_disponible' => max(0, entrada_int('stock_disponible')),
        ];
    }

    /**
     * Valores iniciales del formulario de alta.
     *
     * @return array<string, mixed>
     */
    private function datosVacios(): array
    {
        return [
            'titulo'           => '',
            'autor'            => '',
            'isbn'             => '',
            'id_categoria'     => null,
            'editorial'        => '',
            'anio'             => null,
            'portada_url'      => '',
            'sinopsis'         => '',
            'stock_total'      => 1,
            'stock_disponible' => 1,
        ];
    }

    /**
     * Convierte un registro de la base de datos en datos de formulario.
     *
     * @param array<string, mixed> $libro
     * @return array<string, mixed>
     */
    private function datosDesdeLibro(array $libro): array
    {
        return [
            'titulo'           => (string) $libro['titulo'],
            'autor'            => (string) $libro['autor'],
            'isbn'             => (string) ($libro['isbn'] ?? ''),
            'id_categoria'     => $libro['id_categoria'] !== null ? (int) $libro['id_categoria'] : null,
            'editorial'        => (string) ($libro['editorial'] ?? ''),
            'anio'             => $libro['anio'] !== null ? (int) $libro['anio'] : null,
            'portada_url'      => (string) ($libro['portada_url'] ?? ''),
            'sinopsis'         => (string) ($libro['sinopsis'] ?? ''),
            'stock_total'      => (int) $libro['stock_total'],
            'stock_disponible' => (int) $libro['stock_disponible'],
        ];
    }

    /**
     * Reglas de validación del formulario de libros.
     *
     * @param array<string, mixed> $datos
     * @return array<string, string>
     */
    private function validar(array $datos, ?int $id): array
    {
        $errores = [];

        if ($datos['titulo'] === '') {
            $errores['titulo'] = 'El título es obligatorio.';
        } elseif (mb_strlen($datos['titulo'], 'UTF-8') > 255) {
            $errores['titulo'] = 'El título no puede superar los 255 caracteres.';
        }

        if ($datos['autor'] === '') {
            $errores['autor'] = 'El autor es obligatorio.';
        } elseif (mb_strlen($datos['autor'], 'UTF-8') > 180) {
            $errores['autor'] = 'El autor no puede superar los 180 caracteres.';
        }

        if ($datos['isbn'] !== '') {
            $longitud = strlen($datos['isbn']);

            if (!in_array($longitud, [10, 13], true)) {
                $errores['isbn'] = 'El ISBN debe tener 10 o 13 dígitos.';
            } elseif ($this->libros->isbnRegistrado($datos['isbn'], $id)) {
                $errores['isbn'] = 'Ese ISBN ya está registrado en otro libro.';
            }
        }

        if ($datos['editorial'] !== '' && mb_strlen($datos['editorial'], 'UTF-8') > 150) {
            $errores['editorial'] = 'La editorial no puede superar los 150 caracteres.';
        }

        if ($datos['anio'] !== null) {
            if ($datos['anio'] < 1400 || $datos['anio'] > (int) date('Y') + 1) {
                $errores['anio'] = 'El año debe estar entre 1400 y ' . ((int) date('Y') + 1) . '.';
            }
        }

        if ($datos['id_categoria'] !== null && $this->categorias->obtener((int) $datos['id_categoria']) === null) {
            $errores['id_categoria'] = 'La categoría seleccionada no existe.';
        }

        if ($datos['stock_total'] < 0) {
            $errores['stock_total'] = 'El stock total no puede ser negativo.';
        }

        if ($datos['stock_disponible'] > $datos['stock_total']) {
            $errores['stock_disponible'] = 'Los ejemplares disponibles no pueden superar el stock total.';
        }

        if ($datos['portada_url'] !== '' && preg_match('#^https?://#i', $datos['portada_url']) !== 1) {
            $errores['portada_url'] = 'La dirección de la portada debe comenzar por http:// o https://.';
        }

        return $errores;
    }

    /** Deja el ISBN sólo con dígitos y la X final, igual que los registros existentes. */
    private function normalizarIsbn(string $isbn): string
    {
        return strtoupper((string) preg_replace('/[^0-9Xx]/', '', $isbn));
    }

    /**
     * Parámetros que deben conservarse al paginar.
     *
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    private function parametrosPaginacion(array $filtros): array
    {
        $parametros = [];

        if (($filtros['texto'] ?? '') !== '') {
            $parametros['q'] = $filtros['texto'];
        }

        if ((int) ($filtros['id_categoria'] ?? 0) > 0) {
            $parametros['categoria'] = (int) $filtros['id_categoria'];
        }

        if (($filtros['disponibilidad'] ?? 'todos') !== 'todos') {
            $parametros['disponibilidad'] = $filtros['disponibilidad'];
        }

        if (($filtros['orden'] ?? 'titulo') !== 'titulo') {
            $parametros['orden'] = $filtros['orden'];
        }

        return $parametros;
    }
}
