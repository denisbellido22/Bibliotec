<?php
/**
 * BiblioTech — Marco compartido de las páginas de error.
 *
 * Variables esperadas: $codigoError, $tituloError, $mensajeError y, de forma
 * opcional, $detalleError con información técnica cuando APP_DEBUG está activo.
 */

declare(strict_types=1);

$codigoError   = $codigoError ?? 500;
$tituloError   = $tituloError ?? 'Error';
$mensajeError  = $mensajeError ?? 'Ocurrió un problema inesperado.';
$detalleError  = $detalleError ?? null;
$usuarioError  = function_exists('usuario_actual') ? usuario_actual() : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($codigoError . ' · ' . $tituloError) ?> · BiblioTech</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="pagina pagina--publica">
    <main class="error-pagina">
        <div class="error-pagina__tarjeta">
            <p class="error-pagina__codigo"><?= e((string) $codigoError) ?></p>
            <h1 class="error-pagina__titulo"><?= e($tituloError) ?></h1>
            <p class="error-pagina__mensaje"><?= e($mensajeError) ?></p>

            <?php if ($detalleError !== null && $detalleError !== ''): ?>
                <pre class="error-pagina__detalle"><?= e($detalleError) ?></pre>
            <?php endif; ?>

            <div class="error-pagina__acciones">
                <a class="boton boton--primario" href="<?= e(url('')) ?>">Volver al inicio</a>

                <?php if ($usuarioError === null): ?>
                    <a class="boton boton--secundario" href="<?= e(url('login')) ?>">Iniciar sesión</a>
                <?php else: ?>
                    <a class="boton boton--secundario" href="<?= e(url('catalogo')) ?>">Ir al catálogo</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
