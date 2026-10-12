<?php
/**
 * BiblioTech — Panel del estudiante.
 *
 * Variables: $resumenPrestamos, $resumenReservas, $resumenMultas,
 *            $prestamosAbiertos, $reservas.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Mi cuenta</h1>
        <p class="encabezado__subtitulo">
            Resumen de tu actividad en la biblioteca · <?= e(date('d/m/Y')) ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Buscar un libro</a>
    </div>
</div>

<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica surgir">
            <p class="estadistica__nombre">Préstamos abiertos</p>
            <p class="estadistica__valor"><?= e((string) $resumenPrestamos['activos']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $resumenPrestamos['vencidos']) ?> con retraso ·
                <?= e((string) $resumenPrestamos['devueltos']) ?> devueltos
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Reservas activas</p>
            <p class="estadistica__valor"><?= e((string) $resumenReservas['pendientes']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $resumenReservas['confirmadas']) ?> confirmadas ·
                <?= e((string) $resumenReservas['expiradas']) ?> expiradas
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Multas pendientes</p>
            <p class="estadistica__valor estadistica__valor--dinero">
                <?= e(dinero($resumenMultas['monto_pendiente'])) ?>
            </p>
            <p class="estadistica__nota">
                <?= e((string) $resumenMultas['pendientes']) ?> por pagar ·
                <?= e(dinero($resumenMultas['monto_cobrado'])) ?> pagado
            </p>
        </article>
    </div>
</section>

<?php if ($resumenMultas['pendientes'] > 0): ?>
    <div class="mensaje mensaje--aviso">
        Tienes multas pendientes de pago. Regulariza tu situación para poder reservar nuevos títulos.
    </div>
<?php endif; ?>

<section class="tarjeta">
    <p class="tarjeta__eyebrow">Préstamos</p>
    <h2 class="tarjeta__titulo">Ejemplares en tu poder</h2>

    <?php if ($prestamosAbiertos === []): ?>
        <p class="nota">No tienes préstamos abiertos en este momento.</p>
    <?php else: ?>
        <div class="tabla-envoltura" style="margin-top:.8rem">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Libro</th>
                        <th scope="col">Prestado</th>
                        <th scope="col">Devolver antes de</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamosAbiertos as $prestamo): ?>
                        <?php $retraso = max(0, (int) $prestamo['dias_retraso']); ?>
                        <tr>
                            <td>
                                <span class="tabla__principal"><?= e((string) $prestamo['libro']) ?></span>
                                <span class="tabla__secundario">ISBN: <?= e((string) ($prestamo['libro_isbn'] ?? '—')) ?></span>
                            </td>
                            <td><?= e(fecha_corta((string) $prestamo['fecha_prestamo'])) ?></td>
                            <td>
                                <?= e(fecha_corta((string) $prestamo['fecha_devolucion_esperada'])) ?>
                                <?php if ($retraso > 0): ?>
                                    <span class="tabla__secundario"><?= e((string) $retraso) ?> día(s) de retraso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= e(clase_badge((string) $prestamo['estado'])) ?>">
                                    <?= e(ucfirst((string) $prestamo['estado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="encabezado__acciones" style="margin-top:1rem">
        <a class="boton boton--plano" href="<?= e(url('estudiante/prestamos')) ?>">Ver todos mis préstamos</a>
    </div>
</section>

<section class="tarjeta" style="margin-top:1.2rem">
    <p class="tarjeta__eyebrow">Reservas</p>
    <h2 class="tarjeta__titulo">Últimas reservas</h2>

    <?php if ($reservas === []): ?>
        <p class="nota">Todavía no has reservado ningún libro.</p>
    <?php else: ?>
        <div class="tabla-envoltura" style="margin-top:.8rem">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Libro</th>
                        <th scope="col">Reservada</th>
                        <th scope="col">Vence</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><span class="tabla__principal"><?= e((string) $reserva['libro']) ?></span></td>
                            <td><?= e(fecha_hora((string) $reserva['fecha_reserva'])) ?></td>
                            <td><?= e(fecha_hora((string) $reserva['fecha_expiracion'])) ?></td>
                            <td>
                                <span class="<?= e(clase_badge((string) $reserva['estado'])) ?>">
                                    <?= e(ucfirst((string) $reserva['estado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="encabezado__acciones" style="margin-top:1rem">
        <a class="boton boton--plano" href="<?= e(url('estudiante/reservas')) ?>">Ver todas mis reservas</a>
    </div>
</section>
