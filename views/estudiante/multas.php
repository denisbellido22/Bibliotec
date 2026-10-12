<?php
/**
 * BiblioTech — «Mis multas» del estudiante.
 *
 * Variables: $multas, $resumen.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Mis multas</h1>
        <p class="encabezado__subtitulo">
            Consulta el estado de pago de tus multas por devoluciones con retraso.
        </p>
    </div>
</div>

<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica">
            <p class="estadistica__nombre">Pendiente de pago</p>
            <p class="estadistica__valor estadistica__valor--dinero"><?= e(dinero($resumen['monto_pendiente'])) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['pendientes']) ?> multa(s) pendiente(s)</p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Ya pagado</p>
            <p class="estadistica__valor estadistica__valor--ok"><?= e(dinero($resumen['monto_cobrado'])) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['pagadas']) ?> multa(s) pagada(s)</p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Condonadas</p>
            <p class="estadistica__valor"><?= e((string) $resumen['condonadas']) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['total']) ?> multas en total</p>
        </article>
    </div>
</section>

<?php if ($resumen['pendientes'] > 0): ?>
    <div class="mensaje mensaje--aviso">
        Tienes multas pendientes de pago. Mientras existan, no podrás registrar nuevas reservas.
    </div>
<?php endif; ?>

<?php if ($multas === []): ?>
    <div class="vacio">
        <h3>No tienes multas registradas</h3>
        <p>Devuelve tus préstamos dentro del plazo para mantener tu cuenta al día.</p>
        <a class="boton boton--plano" href="<?= e(url('catalogo')) ?>">Ir al catálogo</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Libro</th>
                    <th scope="col">Retraso</th>
                    <th scope="col">Monto</th>
                    <th scope="col">Generada</th>
                    <th scope="col">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($multas as $multa): ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $multa['libro']) ?></span>
                            <span class="tabla__secundario">Préstamo #<?= e((string) $multa['id_prestamo']) ?></span>
                        </td>
                        <td><?= e((string) max(0, (int) $multa['dias_retraso'])) ?> día(s)</td>
                        <td><strong><?= e(dinero((float) $multa['monto'])) ?></strong></td>
                        <td><?= e(fecha_hora((string) $multa['fecha_generacion'])) ?></td>
                        <td>
                            <span class="<?= e(clase_badge((string) $multa['estado'])) ?>">
                                <?= e(ucfirst((string) $multa['estado'])) ?>
                            </span>
                            <?php if (!empty($multa['fecha_pago'])): ?>
                                <span class="tabla__secundario">Pagada el <?= e(fecha_hora((string) $multa['fecha_pago'])) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="nota" style="margin-top:1rem">
        Los pagos se registran presencialmente en la biblioteca; el sistema no procesa pagos en línea.
    </p>
<?php endif; ?>
