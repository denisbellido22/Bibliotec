<?php
/**
 * BiblioTech — Tarjeta de libro para el catálogo.
 *
 * Variables esperadas: $libro (fila de la tabla `libros` con `categoria`).
 */

declare(strict_types=1);

$disponible = (int) $libro['stock_disponible'];
$total      = (int) $libro['stock_total'];
?>
<article class="libro-tarjeta surgir">
    <a class="libro-tarjeta__portada" href="<?= e(url('catalogo/ver', ['id' => (int) $libro['id']])) ?>"
       aria-label="Ver detalle de <?= e((string) $libro['titulo']) ?>">
        <img src="<?= e(portada_url($libro['portada_url'] ?? null)) ?>"
             alt="Portada de <?= e((string) $libro['titulo']) ?>" loading="lazy">
    </a>

    <div class="libro-tarjeta__cuerpo">
        <h3 class="libro-tarjeta__titulo"><?= e((string) $libro['titulo']) ?></h3>
        <p class="libro-tarjeta__autor"><?= e((string) $libro['autor']) ?></p>

        <?php if (!empty($libro['categoria'])): ?>
            <span class="categoria-chip"><?= e((string) $libro['categoria']) ?></span>
        <?php endif; ?>

        <div class="libro-tarjeta__pie">
            <span class="<?= e(clase_stock($disponible, $total)) ?>">
                <?= e($disponible . ' / ' . $total) ?>
            </span>
            <span class="badge <?= $disponible > 0 ? 'badge--ok' : 'badge--peligro' ?>">
                <?= $disponible > 0 ? 'Disponible' : 'Agotado' ?>
            </span>
        </div>
    </div>
</article>
