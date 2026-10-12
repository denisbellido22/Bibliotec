<?php
/**
 * BiblioTech — Vista del catálogo de libros.
 *
 * Variables: $resultado, $filtros, $categorias, $parametros.
 */

declare(strict_types=1);

$usuarioCat = usuario_actual();
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Catálogo de libros</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resultado['total']) ?>
            <?= $resultado['total'] === 1 ? 'título encontrado' : 'títulos encontrados' ?>
            <?php if ($resultado['paginas'] > 1): ?>
                · página <?= e((string) $resultado['pagina']) ?> de <?= e((string) $resultado['paginas']) ?>
            <?php endif; ?>
            <?php if ($usuarioCat === null): ?>
                · consulta pública
            <?php endif; ?>
        </p>
    </div>
</div>

<?php /* Buscador y filtros (RF05, RF06, RF07) */ ?>
<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="catalogo">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por título, autor o ISBN</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>"
                   placeholder="Ej.: Clean Code, Martin, 9780132350884">
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

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="orden">Ordenar por</label>
            <select class="campo__control" id="orden" name="orden">
                <option value="titulo" <?= $filtros['orden'] === 'titulo' ? 'selected' : '' ?>>Título</option>
                <option value="autor" <?= $filtros['orden'] === 'autor' ? 'selected' : '' ?>>Autor</option>
                <option value="anio" <?= $filtros['orden'] === 'anio' ? 'selected' : '' ?>>Año</option>
                <option value="reciente" <?= $filtros['orden'] === 'reciente' ? 'selected' : '' ?>>Recién agregados</option>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url('catalogo')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($resultado['datos'] === []): ?>
    <div class="vacio">
        <h3>No se encontraron libros</h3>
        <p>Prueba con otro título, autor, ISBN o cambia los filtros aplicados.</p>
        <a class="boton boton--secundario" href="<?= e(url('catalogo')) ?>">Ver todo el catálogo</a>
    </div>
<?php else: ?>
    <section class="catalogo">
        <?php foreach ($resultado['datos'] as $libro): ?>
            <?php require BASE_PATH . '/views/partials/libro-tarjeta.php'; ?>
        <?php endforeach; ?>
    </section>

    <?php
    $pagina     = $resultado['pagina'];
    $paginas    = $resultado['paginas'];
    $baseRuta   = 'catalogo';
    $parametros = $parametros;
    require BASE_PATH . '/views/partials/paginacion.php';
    ?>
<?php endif; ?>
