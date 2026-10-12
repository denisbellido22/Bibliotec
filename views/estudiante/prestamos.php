<?php
/**
 * BiblioTech — «Mis préstamos» del estudiante.
 *
 * Variables: $prestamos, $resumen.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Mis préstamos</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resumen['activos']) ?> abiertos ·
            <?= e((string) $resumen['vencidos']) ?> con retraso ·
            <?= e((string) $resumen['devueltos']) ?> devueltos
        </p>
    </div>
</div>

<?php if ($resumen['vencidos'] > 0): ?>
    <div class="mensaje mensaje--error">
        Tienes <?= e((string) $resumen['vencidos']) ?> préstamo(s) con retraso.
        Se genera una multa de <?= e(dinero((float) config('prestamos.multa_por_dia'))) ?> por día.
    </div>
<?php endif; ?>

<?php if ($prestamos === []): ?>
    <div class="vacio">
        <h3>Sin préstamos registrados</h3>
        <p>Cuando retires un ejemplar aparecerá aquí con su fecha de devolución.</p>
        <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Explorar catálogo</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Libro</th>
                    <th scope="col">Prestado</th>
                    <th scope="col">Devolución esperada</th>
                    <th scope="col">Devuelto</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="tabla__numero">Multa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $prestamo): ?>
                    <?php
                    $abierto = in_array($prestamo['estado'], ['activo', 'vencido'], true);
                    $retraso = max(0, (int) $prestamo['dias_retraso']);
                    ?>
                    <tr>
                        <td>#<?= e((string) $prestamo['id']) ?></td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $prestamo['libro']) ?></span>
                            <span class="tabla__secundario">ISBN: <?= e((string) ($prestamo['libro_isbn'] ?? '—')) ?></span>
                        </td>
                        <td><?= e(fecha_corta((string) $prestamo['fecha_prestamo'])) ?></td>
                        <td>
                            <?= e(fecha_corta((string) $prestamo['fecha_devolucion_esperada'])) ?>
                            <?php if ($abierto && $retraso > 0): ?>
                                <span class="tabla__secundario"><?= e((string) $retraso) ?> día(s) de retraso</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(fecha_corta($prestamo['fecha_devolucion_real'])) ?></td>
                        <td>
                            <span class="<?= e(clase_badge((string) $prestamo['estado'])) ?>">
                                <?= e(ucfirst((string) $prestamo['estado'])) ?>
                            </span>
                        </td>
                        <td class="tabla__numero">
                            <?php if (!empty($prestamo['id_multa'])): ?>
                                <span class="badge badge--alerta">Registrada</span>
                            <?php elseif ($abierto && $retraso > 0): ?>
                                <span class="nota"><?= e(dinero(calcular_multa($retraso))) ?> estimado</span>
                            <?php else: ?>
                                <span class="nota">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="nota" style="margin-top:1rem">
        Las devoluciones se registran en la biblioteca. Consulta tus multas en
        <a href="<?= e(url('estudiante/multas')) ?>">Mis multas</a>.
    </p>
<?php endif; ?>
