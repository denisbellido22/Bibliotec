<?php
/**
 * BiblioTech — Listado administrativo de libros.
 *
 * Variables: $resultado, $filtros, $categorias, $resumen, $parametros.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Libros</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resultado['total']) ?> registros ·
            <?= e((string) $resumen['ejemplares']) ?> ejemplares ·
            <?= e((string) $resumen['disponibles']) ?> disponibles
            <?php if ($resultado['paginas'] > 1): ?>
                · página <?= e((string) $resultado['pagina']) ?> de <?= e((string) $resultado['paginas']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('admin/libros/crear')) ?>">Registrar libro</a>
    </div>
</div>

<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="admin/libros">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por título, autor o ISBN</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>" placeholder="Ej.: Clean Code, Martin, 9780132350884">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="categoria">Categoría</label>
            <select class="campo__control" id="categoria" name="categoria">
                <option value="0">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= e((string) $categoria['id']) ?>"
                        <?= (int) $filtros['id_categoria'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                        <?= e((string) $categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="disponibilidad">Disponibilidad</label>
            <select class="campo__control" id="disponibilidad" name="disponibilidad">
                <option value="todos" <?= $filtros['disponibilidad'] === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="disponibles" <?= $filtros['disponibilidad'] === 'disponibles' ? 'selected' : '' ?>>Con ejemplares</option>
                <option value="agotados" <?= $filtros['disponibilidad'] === 'agotados' ? 'selected' : '' ?>>Agotados</option>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url('admin/libros')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($resultado['datos'] === []): ?>
    <div class="vacio">
        <h3>No hay libros que coincidan</h3>
        <p>Ajusta la búsqueda o registra un título nuevo.</p>
        <a class="boton boton--primario" href="<?= e(url('admin/libros/crear')) ?>">Registrar libro</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Título</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Categoría</th>
                    <th scope="col">Stock</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultado['datos'] as $libro): ?>
                    <?php
                    $disponible = (int) $libro['stock_disponible'];
                    $total      = (int) $libro['stock_total'];
                    ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $libro['titulo']) ?></span>
                            <span class="tabla__secundario">
                                ISBN: <?= e($libro['isbn'] !== null && $libro['isbn'] !== '' ? (string) $libro['isbn'] : 'no registrado') ?>
                                <?php if (!empty($libro['editorial'])): ?>
                                    · <?= e((string) $libro['editorial']) ?>
                                <?php endif; ?>
                                <?php if (!empty($libro['anio'])): ?>
                                    · <?= e((string) $libro['anio']) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?= e((string) $libro['autor']) ?></td>
                        <td>
                            <?php if (!empty($libro['categoria'])): ?>
                                <span class="categoria-chip"><?= e((string) $libro['categoria']) ?></span>
                            <?php else: ?>
                                <span class="badge badge--neutro">Sin categoría</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= e(clase_stock($disponible, $total)) ?>">
                                <?= e($disponible . ' / ' . $total) ?>
                            </span>
                        </td>
                        <td class="tabla__numero">
                            <div class="tabla__acciones">
                                <a class="boton boton--pequeno boton--plano"
                                   href="<?= e(url('admin/libros/ver', ['id' => (int) $libro['id']])) ?>">Ver</a>
                                <a class="boton boton--pequeno boton--secundario"
                                   href="<?= e(url('admin/libros/editar', ['id' => (int) $libro['id']])) ?>">Editar</a>

                                <form method="post" action="<?= e(url('admin/libros/eliminar', ['id' => (int) $libro['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--pequeno boton--peligro" type="submit"
                                            data-confirmar="¿Eliminar «<?= e((string) $libro['titulo']) ?>»? Esta acción no se puede deshacer.">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pagina     = $resultado['pagina'];
    $paginas    = $resultado['paginas'];
    $baseRuta   = 'admin/libros';
    require BASE_PATH . '/views/partials/paginacion.php';
    ?>
<?php endif; ?>
