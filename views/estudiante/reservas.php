<?php
/**
 * BiblioTech — «Mis reservas» del estudiante.
 *
 * Variables: $reservas, $resumen.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Mis reservas</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resumen['pendientes']) ?> pendientes ·
            <?= e((string) $resumen['confirmadas']) ?> confirmadas ·
            <?= e((string) $resumen['expiradas']) ?> expiradas
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Reservar un libro</a>
    </div>
</div>

<?php if ($reservas === []): ?>
    <div class="vacio">
        <h3>Todavía no tienes reservas</h3>
        <p>Busca un libro en el catálogo y usa el botón «Reservar» de su ficha.</p>
        <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Explorar catálogo</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Libro</th>
                    <th scope="col">Reservada</th>
                    <th scope="col">Vence</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <?php $expirada = $reserva['estado'] === 'pendiente' && strtotime((string) $reserva['fecha_expiracion']) < time(); ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $reserva['libro']) ?></span>
                            <span class="tabla__secundario">
                                Disponibles: <?= e((string) $reserva['stock_disponible']) ?>/<?= e((string) $reserva['stock_total']) ?>
                            </span>
                        </td>
                        <td><?= e(fecha_hora((string) $reserva['fecha_reserva'])) ?></td>
                        <td><?= e(fecha_hora((string) $reserva['fecha_expiracion'])) ?></td>
                        <td>
                            <span class="<?= e(clase_badge((string) $reserva['estado'])) ?>">
                                <?= e(ucfirst((string) $reserva['estado'])) ?>
                            </span>
                            <?php if ($expirada): ?>
                                <span class="tabla__secundario">Fuera de plazo</span>
                            <?php endif; ?>
                        </td>
                        <td class="tabla__numero">
                            <div class="tabla__acciones">
                                <a class="boton boton--pequeno boton--plano"
                                   href="<?= e(url('catalogo/ver', ['id' => (int) $reserva['id_libro']])) ?>">Ver libro</a>

                                <?php if ($reserva['estado'] === 'pendiente'): ?>
                                    <form method="post"
                                          action="<?= e(url('estudiante/reservas/cancelar', ['id' => (int) $reserva['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--peligro" type="submit"
                                                data-confirmar="¿Cancelar tu reserva de «<?= e((string) $reserva['libro']) ?>»?">
                                            Cancelar
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
