<?php
declare(strict_types=1);

$codigoError  = 403;
$tituloError  = 'Acceso denegado';
$mensajeError = $mensaje ?? 'Tu rol no tiene permisos para acceder a esta sección.';

require __DIR__ . '/_base.php';
