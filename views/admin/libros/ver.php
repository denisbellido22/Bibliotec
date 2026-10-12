<?php
/**
 * BiblioTech — Ficha administrativa del libro.
 *
 * Variables: $libro.
 */

declare(strict_types=1);

$disponible = (int) $libro['stock_disponible'];
$total      = (int) $libro['stock_total'];
$prestados  = max(0, $total - $disponible);
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo"><?= e((string) $libro['titulo']) ?></h1>
        <p class="encabezado__subtitulo"><?= e((string) $libro['autor']) ?></p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--secundario" href="<?= e(url('admin/libros/editar', ['id' => (int) $libro['id']])) ?>">Editar</a>
        <a class="boton boton--plano" href="<?= e(url('catalogo/ver', ['id' => (int) $libro['id']])) ?>">Ver en catálogo</a>
        <a class="boton boton--plano" href="<?= e(url('admin/libros')) ?>">Volver</a>
    </div>
</div>

<section class="rejilla rejilla--dos">
    <div class="tarjeta">
        <img class="ficha-libro__portada"
             src="<?= e(portada_url($libro['portada_url'] ?? null)) ?>"
             alt="Portada de <?= e((string) $libro['titulo']) ?>">
    </div>

    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Datos registrados</p>
        <h2 class="tarjeta__titulo">Información del título</h2>

        <ul class="lista-datos">
            <li><span>ISBN</span><strong><?= e($libro['isbn'] !== null && $libro['isbn'] !== '' ? (string) $libro['isbn'] : 'No registrado') ?></strong></li>
            <li><span>Categoría</span><strong><?= e($libro['categoria'] !== null ? (string) $libro['categoria'] : 'Sin categoría') ?></strong></li>
            <li><span>Editorial</span><strong><?= e($libro['editorial'] !== null && $libro['editorial'] !== '' ? (string) $libro['editorial'] : 'No registrada') ?></strong></li>
            <li><span>Año</span><strong><?= e($libro['anio'] !== null ? (string) $libro['anio'] : 'No registrado') ?></strong></li>
            <li><span>Stock total</span><strong><?= e((string) $total) ?></strong></li>
            <li><span>Disponibles</span><strong><?= e((string) $disponible) ?></strong></li>
            <li><span>En préstamo</span><strong><?= e((string) $prestados) ?></strong></li>
            <li><span>Registrado</span><strong><?= e(fecha_hora($libro['created_at'] ?? null)) ?></strong></li>
            <li><span>Última actualización</span><strong><?= e(fecha_hora($libro['updated_at'] ?? null)) ?></strong></li>
        </ul>

        <p class="nota">
            Estado del stock:
            <span class="<?= e(clase_stock($disponible, $total)) ?>"><?= e($disponible . ' / ' . $total) ?></span>
        </p>
    </div>
</section>

<section class="tarjeta" style="margin-top:1.2rem">
    <p class="tarjeta__eyebrow">Sinopsis</p>
    <p>
        <?= $libro['sinopsis'] !== null && $libro['sinopsis'] !== ''
            ? nl2br(e((string) $libro['sinopsis']))
            : 'Sin sinopsis registrada.' ?>
    </p>
</section>
