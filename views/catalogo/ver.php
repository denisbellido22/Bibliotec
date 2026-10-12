<?php
/**
 * BiblioTech — Ficha detallada de un libro.
 *
 * Variables: $libro.
 */

declare(strict_types=1);

$disponible = (int) $libro['stock_disponible'];
$total      = (int) $libro['stock_total'];
$prestados  = max(0, $total - $disponible);
$usuarioVer = usuario_actual();
?>
<div class="encabezado">
    <div>
        <p class="etiqueta-superior">Ficha del libro</p>
        <h1 class="encabezado__titulo"><?= e((string) $libro['titulo']) ?></h1>
        <p class="encabezado__subtitulo"><?= e((string) $libro['autor']) ?></p>
    </div>

    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('catalogo')) ?>">Volver al catálogo</a>
    </div>
</div>

<section class="rejilla rejilla--dos">
    <div class="tarjeta">
        <img class="ficha-libro__portada"
             src="<?= e(portada_url($libro['portada_url'] ?? null)) ?>"
             alt="Portada de <?= e((string) $libro['titulo']) ?>">
    </div>

    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Información</p>
        <h2 class="tarjeta__titulo">Datos de la obra</h2>

        <ul class="lista-datos">
            <li><span>ISBN</span><strong><?= e($libro['isbn'] !== null && $libro['isbn'] !== '' ? (string) $libro['isbn'] : 'No registrado') ?></strong></li>
            <li><span>Categoría</span><strong><?= e($libro['categoria'] !== null ? (string) $libro['categoria'] : 'Sin categoría') ?></strong></li>
            <li><span>Editorial</span><strong><?= e($libro['editorial'] !== null && $libro['editorial'] !== '' ? (string) $libro['editorial'] : 'No registrada') ?></strong></li>
            <li><span>Año de publicación</span><strong><?= e($libro['anio'] !== null ? (string) $libro['anio'] : 'No registrado') ?></strong></li>
            <li><span>Stock total</span><strong><?= e((string) $total) ?> ejemplares</strong></li>
            <li><span>Disponibles</span><strong><?= e((string) $disponible) ?> ejemplares</strong></li>
            <li><span>En préstamo</span><strong><?= e((string) $prestados) ?> ejemplares</strong></li>
        </ul>

        <p class="nota">
            Estado actual:
            <span class="<?= e(clase_stock($disponible, $total)) ?>"><?= e($disponible . ' / ' . $total) ?></span>
            <span class="badge <?= $disponible > 0 ? 'badge--ok' : 'badge--peligro' ?>">
                <?= $disponible > 0 ? 'Disponible' : 'Agotado' ?>
            </span>
        </p>

        <?php /* El estudiante autenticado puede reservar si hay ejemplares. */ ?>
        <?php if (($usuarioVer['rol'] ?? null) === 'estudiante'): ?>
            <?php if ($disponible > 0): ?>
                <form method="post" action="<?= e(url('estudiante/reservas/crear')) ?>"
                      data-unico-envio style="margin-top:.9rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_libro" value="<?= e((string) $libro['id']) ?>">
                    <input type="hidden" name="origen" value="ficha">
                    <button class="boton boton--primario" type="submit">Reservar este libro</button>
                </form>
                <p class="nota" style="margin-top:.5rem">
                    Plazo de retiro: <?= e((string) config('reservas.dias_vigencia')) ?> días desde la reserva.
                </p>
            <?php else: ?>
                <p class="nota" style="margin-top:.9rem">
                    No es posible reservar: el título no tiene ejemplares disponibles.
                </p>
            <?php endif; ?>
        <?php elseif ($usuarioVer === null): ?>
            <p class="nota" style="margin-top:.9rem">
                <a href="<?= e(url('login', ['destino' => 'catalogo/ver'])) ?>">Inicia sesión</a>
                como estudiante para reservar este título.
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="tarjeta" style="margin-top:1.2rem">
    <p class="tarjeta__eyebrow">Sinopsis</p>
    <p>
        <?= $libro['sinopsis'] !== null && $libro['sinopsis'] !== ''
            ? nl2br(e((string) $libro['sinopsis']))
            : 'Sin sinopsis registrada.' ?>
    </p>

    <?php if (!empty($libro['categoria_descripcion'])): ?>
        <p class="nota">
            <strong><?= e((string) $libro['categoria']) ?>:</strong>
            <?= e((string) $libro['categoria_descripcion']) ?>
        </p>
    <?php endif; ?>
</section>
