<?php
/**
 * BiblioTech — Barra lateral de los paneles por rol.
 */

declare(strict_types=1);

$usuarioLateral = usuario_actual();
$opciones       = menu_rol($usuarioLateral);
$rutaActual     = ruta_actual();
?>
<aside class="lateral" aria-label="Menú del panel">
    <div class="lateral__tarjeta">
        <p class="lateral__titulo"><?= e(titulo_panel($usuarioLateral)) ?></p>

        <nav class="lateral__menu">
            <?php foreach ($opciones as $opcion): ?>
                <?php
                // Sólo se enlazan módulos realmente registrados en el enrutador.
                if (!ruta_existe($opcion['ruta'])) {
                    continue;
                }

                // Una sección queda activa también en sus subpáginas.
                $activa = $rutaActual === $opcion['ruta']
                    || str_starts_with($rutaActual, $opcion['ruta'] . '/');
                ?>
                <a class="lateral__enlace <?= $activa ? 'esta-activo' : '' ?>"
                   href="<?= e(url($opcion['ruta'])) ?>"
                   <?= $activa ? 'aria-current="page"' : '' ?>>
                    <?= e($opcion['etiqueta']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>
