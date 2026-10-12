<?php
declare(strict_types=1);

$codigoError  = 500;
$tituloError  = 'Error interno';
$mensajeError = $mensaje ?? 'Ocurrió un problema al procesar la solicitud.';
$detalleError = $detalle ?? null;

require __DIR__ . '/_base.php';
