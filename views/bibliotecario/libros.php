<?php
/**
 * BiblioTech — Consulta del catálogo por el bibliotecario (RF04).
 *
 * Variables: $resultado, $filtros, $categorias, $parametros.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Libros</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resultado['total']) ?> registros encontrados · sólo lectura
            <?php if ($resultado['paginas'] > 1): ?>
                · página <?= e((string) $resultado['pagina']) ?> de <?= e((string) $resultado['paginas']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('bibliotecario/prestamos/crear')) ?>">Registrar préstamo</a>
    </div>
</div>

<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="bibliotecario/libros">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por título, autor o ISBN</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>" placeholder="Ej.: Clean Code, Martin, 9780132350884">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="categoria">Categoría</label>
            <select class="campo__control" id="categoria" name="categoria">
                <option value="0">Todas las categorías</option>
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
            <a class="boton boton--plano" href="<?= e(url('bibliotecario/libros')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($resultado['datos'] === []): ?>
    <div class="vacio">
        <h3>No se encontraron libros</h3>
        <p>Prueba con otro título, autor, ISBN o cambia los filtros.</p>
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
                            <span class="tabla__secundario">ISBN: <?= e((string) ($libro['isbn'] ?? 'no registrado')) ?></span>
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
                            <a class="boton boton--pequeno boton--plano"
                               href="<?= e(url('catalogo/ver', ['id' => (int) $libro['id']])) ?>">Ver ficha</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pagina     = $resultado['pagina'];
    $paginas    = $resultado['paginas'];
    $baseRuta   = 'bibliotecario/libros';
    require BASE_PATH . '/views/partials/paginacion.php';
    ?>
<?php endif; ?>
