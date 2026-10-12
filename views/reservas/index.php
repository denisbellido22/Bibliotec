<?php
/**
 * BiblioTech — Listado de reservas (administrador y bibliotecario).
 *
 * Variables: $reservas, $filtros, $resumen, $prefijo.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Reservas</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resumen['pendientes']) ?> pendientes ·
            <?= e((string) $resumen['confirmadas']) ?> confirmadas ·
            <?= e((string) $resumen['expiradas']) ?> expiradas ·
            <?= e((string) $resumen['canceladas']) ?> canceladas
        </p>
    </div>
</div>

<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="<?= e($prefijo . '/reservas') ?>">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por usuario, correo o libro</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="estado">Estado</label>
            <select class="campo__control" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (Reserva::ESTADOS as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                        <?= e(ucfirst($estado)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url($prefijo . '/reservas')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($reservas === []): ?>
    <div class="vacio">
        <h3>No hay reservas que coincidan</h3>
        <p>Las reservas se crean desde la ficha de cada libro en el catálogo.</p>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Libro</th>
                    <th scope="col">Reservada</th>
                    <th scope="col">Expira</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <?php $expirada = $reserva['estado'] === 'pendiente' && strtotime((string) $reserva['fecha_expiracion']) < time(); ?>
                    <tr>
                        <td>#<?= e((string) $reserva['id']) ?></td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $reserva['usuario']) ?></span>
                            <span class="tabla__secundario"><?= e((string) $reserva['usuario_email']) ?></span>
                        </td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $reserva['libro']) ?></span>
                            <span class="tabla__secundario">
                                Disponibles: <?= e((string) $reserva['stock_disponible']) ?>/<?= e((string) $reserva['stock_total']) ?>
                            </span>
                        </td>
                        <td><?= e(fecha_hora((string) $reserva['fecha_reserva'])) ?></td>
                        <td>
                            <?= e(fecha_hora((string) $reserva['fecha_expiracion'])) ?>
                            <?php if ($expirada): ?>
                                <span class="tabla__secundario">Fuera de plazo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= e(clase_badge((string) $reserva['estado'])) ?>">
                                <?= e(ucfirst((string) $reserva['estado'])) ?>
                            </span>
                            <?php if (!empty($reserva['id_prestamo'])): ?>
                                <span class="tabla__secundario">Préstamo #<?= e((string) $reserva['id_prestamo']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="tabla__numero">
                            <?php if ($reserva['estado'] === 'pendiente'): ?>
                                <div class="tabla__acciones">
                                    <form method="post"
                                          action="<?= e(url($prefijo . '/reservas/confirmar', ['id' => (int) $reserva['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--exito" type="submit">Confirmar</button>
                                    </form>

                                    <form method="post"
                                          action="<?= e(url($prefijo . '/reservas/cancelar', ['id' => (int) $reserva['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--peligro" type="submit"
                                                data-confirmar="¿Cancelar la reserva de <?= e((string) $reserva['usuario']) ?>?">
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="tabla__secundario">Sin acciones</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
