<?php
/**
 * BiblioTech — Ficha del préstamo.
 *
 * Variables: $prestamo, $prefijo.
 */

declare(strict_types=1);

$abierto = in_array($prestamo['estado'], ['activo', 'vencido'], true);
$retraso = max(0, (int) $prestamo['dias_retraso']);
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Préstamo #<?= e((string) $prestamo['id']) ?></h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $prestamo['libro']) ?> · <?= e((string) $prestamo['usuario']) ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <?php if ($abierto): ?>
            <form method="post" action="<?= e(url($prefijo . '/prestamos/devolver', ['id' => (int) $prestamo['id']])) ?>">
                <?= csrf_field() ?>
                <button class="boton boton--exito" type="submit"
                        data-confirmar="¿Registrar la devolución de este ejemplar?">
                    Registrar devolución
                </button>
            </form>
        <?php endif; ?>

        <a class="boton boton--plano" href="<?= e(url($prefijo . '/prestamos')) ?>">Volver al listado</a>
    </div>
</div>

<section class="rejilla rejilla--dos">
    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Préstamo</p>
        <h2 class="tarjeta__titulo">Fechas y estado</h2>

        <ul class="lista-datos">
            <li><span>Fecha de préstamo</span><strong><?= e(fecha_corta((string) $prestamo['fecha_prestamo'])) ?></strong></li>
            <li><span>Devolución esperada</span><strong><?= e(fecha_corta((string) $prestamo['fecha_devolucion_esperada'])) ?></strong></li>
            <li><span>Devolución real</span><strong><?= e(fecha_corta($prestamo['fecha_devolucion_real'])) ?></strong></li>
            <li>
                <span>Estado</span>
                <strong>
                    <span class="<?= e(clase_badge((string) $prestamo['estado'])) ?>">
                        <?= e(ucfirst((string) $prestamo['estado'])) ?>
                    </span>
                </strong>
            </li>
            <?php if ($abierto && $retraso > 0): ?>
                <li>
                    <span>Días de retraso</span>
                    <strong><?= e((string) $retraso) ?> día(s) ·
                        <?= e(dinero(calcular_multa($retraso))) ?> estimado</strong>
                </li>
            <?php endif; ?>
            <li><span>Reserva vinculada</span><strong><?= e($prestamo['id_reserva'] !== null ? '#' . $prestamo['id_reserva'] : 'Ninguna') ?></strong></li>
        </ul>
    </div>

    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Participantes</p>
        <h2 class="tarjeta__titulo">Usuario y ejemplar</h2>

        <ul class="lista-datos">
            <li><span>Usuario</span><strong><?= e((string) $prestamo['usuario']) ?></strong></li>
            <li><span>Correo</span><strong><?= e((string) $prestamo['usuario_email']) ?></strong></li>
            <li><span>Rol</span><strong><?= e(nombre_rol((string) $prestamo['usuario_rol'])) ?></strong></li>
            <li><span>Libro</span><strong><?= e((string) $prestamo['libro']) ?></strong></li>
            <li><span>Autor</span><strong><?= e((string) $prestamo['libro_autor']) ?></strong></li>
            <li><span>ISBN</span><strong><?= e((string) ($prestamo['libro_isbn'] ?? '—')) ?></strong></li>
        </ul>
    </div>
</section>

<?php if (!empty($prestamo['multa'])): ?>
    <section class="tarjeta" style="margin-top:1.2rem">
        <p class="tarjeta__eyebrow">Multa asociada</p>
        <h2 class="tarjeta__titulo">Cargo por retraso</h2>

        <ul class="lista-datos">
            <li><span>Importe</span><strong><?= e(dinero((float) $prestamo['multa']['monto'])) ?></strong></li>
            <li>
                <span>Estado</span>
                <strong>
                    <span class="<?= e(clase_badge((string) $prestamo['multa']['estado'])) ?>">
                        <?= e(ucfirst((string) $prestamo['multa']['estado'])) ?>
                    </span>
                </strong>
            </li>
            <li><span>Generada</span><strong><?= e(fecha_hora($prestamo['multa']['fecha_generacion'])) ?></strong></li>
            <li><span>Pagada</span><strong><?= e(fecha_hora($prestamo['multa']['fecha_pago'])) ?></strong></li>
        </ul>

        <?php if (ruta_existe($prefijo . '/multas')): ?>
            <a class="boton boton--plano" href="<?= e(url($prefijo . '/multas')) ?>">Ver todas las multas</a>
        <?php endif; ?>
    </section>
<?php endif; ?>
