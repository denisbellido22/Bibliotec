<?php
/**
 * BiblioTech — Plantilla centrada para la pantalla de acceso.
 *
 * Variables esperadas: $contenido, $titulo.
 */

declare(strict_types=1);

$titulo = $titulo ?? 'Acceso';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo . ' · ' . (string) config('app.nombre')) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="pagina pagina--acceso">
    <main class="acceso">
        <a class="acceso__marca" href="<?= e(url('')) ?>">
            <span class="marca__icono" aria-hidden="true">BT</span>
            <span><?= e((string) config('app.nombre')) ?></span>
        </a>
        <p class="acceso__subtitulo"><?= e((string) config('app.subtitulo')) ?></p>

        <?php require BASE_PATH . '/views/partials/mensajes.php'; ?>

        <?= $contenido ?>
    </main>

    <p class="acceso__pie">
        <a href="<?= e(url('')) ?>">Volver al inicio</a> ·
        <a href="<?= e(url('catalogo')) ?>">Consultar catálogo</a>
    </p>

    <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
