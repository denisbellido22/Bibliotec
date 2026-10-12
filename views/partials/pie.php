<?php
/**
 * BiblioTech — Pie de página.
 */

declare(strict_types=1);
?>
<footer class="pie">
    <p>
        <strong><?= e((string) config('app.nombre')) ?></strong>
        · <?= e((string) config('app.subtitulo')) ?>
        · v<?= e((string) config('app.version')) ?>
    </p>
    <p class="pie__nota">Desarrollado en PHP puro con PDO sobre la base de datos Bibliotec.</p>
</footer>
