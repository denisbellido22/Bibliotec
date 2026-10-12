<?php
declare(strict_types=1);

$codigoError  = 419;
$tituloError  = 'Formulario expirado';
$mensajeError = $mensaje ?? 'El formulario perdió vigencia por seguridad. Recarga la página y vuelve a enviarlo.';

require __DIR__ . '/_base.php';
