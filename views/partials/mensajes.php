<?php
/**
 * BiblioTech — Mensajes flash (éxito, error, aviso, información).
 */

declare(strict_types=1);

$mensajesPendientes = flashes();

if ($mensajesPendientes === []) {
    return;
}
?>
<div class="mensajes" role="status" aria-live="polite">
    <?php foreach ($mensajesPendientes as $mensaje): ?>
        <?php
        $tipo = match ($mensaje['tipo'] ?? 'info') {
            'exito'  => 'mensaje--exito',
            'error'  => 'mensaje--error',
            'aviso'  => 'mensaje--aviso',
            default  => 'mensaje--info',
        };
        ?>
        <div class="mensaje <?= e($tipo) ?>">
            <?= e($mensaje['mensaje'] ?? '') ?>
        </div>
    <?php endforeach; ?>
</div>
