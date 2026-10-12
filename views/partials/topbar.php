<?php
/**
 * BiblioTech — Barra superior.
 * Muestra la navegación correspondiente al rol y el botón de cierre de sesión.
 */

declare(strict_types=1);

$usuarioBarra = usuario_actual();
$rutaActual   = ruta_actual();
?>
<header class="barra-superior">
    <a class="marca" href="<?= e(url('')) ?>">
        <span class="marca__icono" aria-hidden="true">BT</span>
        <span class="marca__texto"><?= e((string) config('app.nombre')) ?></span>
    </a>

    <button class="barra-superior__alternar" type="button" data-menu-alternar
            aria-controls="menu-principal" aria-expanded="false">
        <span class="sr-solo">Abrir menú</span>
        <span aria-hidden="true">☰</span>
    </button>

    <nav class="navegacion" id="menu-principal" aria-label="Navegación principal">
        <a class="navegacion__enlace <?= $rutaActual === '' ? 'esta-activo' : '' ?>"
           href="<?= e(url('')) ?>">Inicio</a>

        <a class="navegacion__enlace <?= $rutaActual === 'catalogo' ? 'esta-activo' : '' ?>"
           href="<?= e(url('catalogo')) ?>">Catálogo</a>

        <?php if ($usuarioBarra !== null): ?>
            <?php if ($usuarioBarra['rol'] === 'administrador'): ?>
                <a class="navegacion__enlace <?= str_starts_with($rutaActual, 'admin/') ? 'esta-activo' : '' ?>"
                   href="<?= e(url('admin/dashboard')) ?>">Admin</a>
            <?php elseif ($usuarioBarra['rol'] === 'bibliotecario'): ?>
                <a class="navegacion__enlace <?= str_starts_with($rutaActual, 'bibliotecario/') ? 'esta-activo' : '' ?>"
                   href="<?= e(url('bibliotecario/panel')) ?>">Panel</a>
            <?php else: ?>
                <a class="navegacion__enlace <?= str_starts_with($rutaActual, 'estudiante/') ? 'esta-activo' : '' ?>"
                   href="<?= e(url('estudiante/panel')) ?>">Mi cuenta</a>
            <?php endif; ?>

            <span class="navegacion__usuario" title="<?= e($usuarioBarra['email']) ?>">
                <?= e($usuarioBarra['nombre']) ?>
                <small><?= e(nombre_rol($usuarioBarra['rol'])) ?></small>
            </span>

            <a class="boton boton--salir" href="<?= e(url('logout')) ?>">Salir</a>
        <?php else: ?>
            <a class="boton boton--salir" href="<?= e(url('login')) ?>">Iniciar sesión</a>
        <?php endif; ?>
    </nav>
</header>
