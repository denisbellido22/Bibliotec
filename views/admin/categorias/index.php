<?php
/**
 * BiblioTech — Listado de categorías.
 *
 * Variables: $categorias.
 */

declare(strict_types=1);

$totalLibros = array_sum(array_map(
    static fn (array $categoria): int => (int) $categoria['total_libros'],
    $categorias
));
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Categorías</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) count($categorias)) ?> categorías ·
            <?= e((string) $totalLibros) ?> libros clasificados
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('admin/categorias/crear')) ?>">Nueva categoría</a>
    </div>
</div>

<?php if ($categorias === []): ?>
    <div class="vacio">
        <h3>Todavía no hay categorías</h3>
        <p>Crea la primera categoría para clasificar el catálogo.</p>
        <a class="boton boton--primario" href="<?= e(url('admin/categorias/crear')) ?>">Nueva categoría</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Categoría</th>
                    <th scope="col">Descripción</th>
                    <th scope="col">Libros</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td><span class="tabla__principal"><?= e((string) $categoria['nombre']) ?></span></td>
                        <td>
                            <?php if (!empty($categoria['descripcion'])): ?>
                                <?= e(truncar((string) $categoria['descripcion'], 90)) ?>
                            <?php else: ?>
                                <span class="tabla__secundario">Sin descripción</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= (int) $categoria['total_libros'] > 0 ? 'badge--info' : 'badge--neutro' ?>">
                                <?= e((string) $categoria['total_libros']) ?>
                            </span>
                        </td>
                        <td class="tabla__numero">
                            <div class="tabla__acciones">
                                <a class="boton boton--pequeno boton--plano"
                                   href="<?= e(url('catalogo', ['categoria' => (int) $categoria['id']])) ?>">Ver libros</a>
                                <a class="boton boton--pequeno boton--secundario"
                                   href="<?= e(url('admin/categorias/editar', ['id' => (int) $categoria['id']])) ?>">Editar</a>

                                <form method="post" action="<?= e(url('admin/categorias/eliminar', ['id' => (int) $categoria['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--pequeno boton--peligro" type="submit"
                                            data-confirmar="¿Eliminar la categoría «<?= e((string) $categoria['nombre']) ?>»?">
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
<?php endif; ?>
