<?php
/**
 * BiblioTech — Plantilla principal.
 *
 * Variables esperadas:
 *   $contenido   HTML ya renderizado de la vista solicitada.
 *   $titulo      Título del documento.
 *   $conSidebar  true para mostrar el menú lateral (paneles por rol).
 */

declare(strict_types=1);

$usuario     = usuario_actual();
$conSidebar  = !empty($conSidebar) && $usuario !== null;
$titulo      = $titulo ?? '';
$tituloDoc   = $titulo !== '' ? $titulo . ' · ' . config('app.nombre') : (string) config('app.nombre');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BiblioTech: sistema de gestión de biblioteca virtual.">
    <title><?= e($tituloDoc) ?></title>

    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="<?= $conSidebar ? 'pagina pagina--panel' : 'pagina pagina--publica' ?>">

<?php require BASE_PATH . '/views/partials/topbar.php'; ?>

<div class="disposicion">
    <?php if ($conSidebar): ?>
        <?php require BASE_PATH . '/views/partials/sidebar.php'; ?>
    <?php endif; ?>

    <main class="principal" id="contenido">
        <?php require BASE_PATH . '/views/partials/mensajes.php'; ?>
        <?= $contenido ?>
    </main>
</div>

<?php require BASE_PATH . '/views/partials/pie.php'; ?>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
