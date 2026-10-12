<?php
/**
 * BiblioTech — Listado de préstamos (panel administrativo y bibliotecario).
 *
 * Variables: $prestamos, $filtros, $resumen, $prefijo.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Préstamos</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resumen['activos']) ?> activos ·
            <?= e((string) $resumen['vencidos']) ?> vencidos ·
            <?= e((string) $resumen['devueltos']) ?> devueltos ·
            <?= e((string) $resumen['del_mes']) ?> este mes
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url($prefijo . '/prestamos/crear')) ?>">Registrar préstamo</a>
    </div>
</div>

<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="<?= e($prefijo . '/prestamos') ?>">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por usuario, correo o libro</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>" placeholder="Ej.: Carlos, Clean Code">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="estado">Estado</label>
            <select class="campo__control" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (Prestamo::ESTADOS as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                        <?= e(ucfirst($estado)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url($prefijo . '/prestamos')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($prestamos === []): ?>
    <div class="vacio">
        <h3>No hay préstamos que coincidan</h3>
        <p>Registra un préstamo nuevo o cambia los filtros aplicados.</p>
        <a class="boton boton--primario" href="<?= e(url($prefijo . '/prestamos/crear')) ?>">Registrar préstamo</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Libro</th>
                    <th scope="col">Préstamo</th>
                    <th scope="col">Devolución esperada</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $prestamo): ?>
                    <?php
                    $vencido = in_array($prestamo['estado'], ['activo', 'vencido'], true)
                        && (int) $prestamo['dias_retraso'] > 0;
                    ?>
                    <tr>
                        <td>#<?= e((string) $prestamo['id']) ?></td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $prestamo['usuario']) ?></span>
                            <span class="tabla__secundario"><?= e((string) $prestamo['usuario_email']) ?></span>
                        </td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $prestamo['libro']) ?></span>
                            <span class="tabla__secundario">ISBN: <?= e((string) ($prestamo['libro_isbn'] ?? '—')) ?></span>
                        </td>
                        <td><?= e(fecha_corta((string) $prestamo['fecha_prestamo'])) ?></td>
                        <td>
                            <?= e(fecha_corta((string) $prestamo['fecha_devolucion_esperada'])) ?>
                            <?php if ($vencido): ?>
                                <span class="tabla__secundario">
                                    <?= e((string) $prestamo['dias_retraso']) ?> día(s) de retraso
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= e(clase_badge((string) $prestamo['estado'])) ?>">
                                <?= e(ucfirst((string) $prestamo['estado'])) ?>
                            </span>
                            <?php if (!empty($prestamo['id_multa'])): ?>
                                <span class="tabla__secundario">Con multa</span>
                            <?php endif; ?>
                        </td>
                        <td class="tabla__numero">
                            <div class="tabla__acciones">
                                <a class="boton boton--pequeno boton--plano"
                                   href="<?= e(url($prefijo . '/prestamos/ver', ['id' => (int) $prestamo['id']])) ?>">Ver</a>

                                <?php if (in_array($prestamo['estado'], ['activo', 'vencido'], true)): ?>
                                    <form method="post"
                                          action="<?= e(url($prefijo . '/prestamos/devolver', ['id' => (int) $prestamo['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--exito" type="submit"
                                                data-confirmar="¿Registrar la devolución de «<?= e((string) $prestamo['libro']) ?>»?">
                                            Devolver
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
