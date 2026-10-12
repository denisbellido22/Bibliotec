<?php
/**
 * BiblioTech — Paginación reutilizable.
 *
 * Variables esperadas:
 *   $pagina      Página actual.
 *   $paginas     Total de páginas.
 *   $baseRuta    Nombre de ruta al que apuntan los enlaces.
 *   $parametros  Parámetros de filtro que deben conservarse.
 */

declare(strict_types=1);

$pagina     = (int) ($pagina ?? 1);
$paginas    = (int) ($paginas ?? 1);
$baseRuta   = (string) ($baseRuta ?? '');
$parametros = (array) ($parametros ?? []);

if ($paginas <= 1) {
    return;
}

$enlace = static function (int $destino) use ($baseRuta, $parametros): string {
    return url($baseRuta, $parametros + ['pagina' => $destino]);
};

// Ventana de páginas visibles alrededor de la actual.
$desde = max(1, $pagina - 2);
$hasta = min($paginas, $pagina + 2);
?>
<nav class="paginacion" aria-label="Paginación del catálogo">
    <a class="paginacion__enlace <?= $pagina <= 1 ? 'esta-inhabilitado' : '' ?>"
       href="<?= e($enlace(max(1, $pagina - 1))) ?>"
       <?= $pagina <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
       rel="prev">Anterior</a>

    <?php if ($desde > 1): ?>
        <a class="paginacion__enlace" href="<?= e($enlace(1)) ?>">1</a>
        <?php if ($desde > 2): ?><span class="paginacion__enlace esta-inhabilitado">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $desde; $i <= $hasta; $i++): ?>
        <a class="paginacion__enlace <?= $i === $pagina ? 'esta-activo' : '' ?>"
           href="<?= e($enlace($i)) ?>"
           <?= $i === $pagina ? 'aria-current="page"' : '' ?>><?= e((string) $i) ?></a>
    <?php endfor; ?>

    <?php if ($hasta < $paginas): ?>
        <?php if ($hasta < $paginas - 1): ?><span class="paginacion__enlace esta-inhabilitado">…</span><?php endif; ?>
        <a class="paginacion__enlace" href="<?= e($enlace($paginas)) ?>"><?= e((string) $paginas) ?></a>
    <?php endif; ?>

    <a class="paginacion__enlace <?= $pagina >= $paginas ? 'esta-inhabilitado' : '' ?>"
       href="<?= e($enlace(min($paginas, $pagina + 1))) ?>"
       <?= $pagina >= $paginas ? 'aria-disabled="true" tabindex="-1"' : '' ?>
       rel="next">Siguiente</a>
</nav>
