<?php
/**
 * BiblioTech — Página principal.
 */

declare(strict_types=1);

$resumen = db_resumen_tablas();
?>
<section class="portada">
    <div class="portada__texto">
        <p class="etiqueta-superior">Biblioteca virtual</p>
        <h1 class="portada__titulo">Bienvenido a BiblioTech</h1>
        <p class="portada__descripcion">
            Consulta el catálogo, revisa la disponibilidad de ejemplares y gestiona
            préstamos, reservas y multas desde un solo lugar.
        </p>

        <div class="portada__acciones">
            <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Explorar catálogo</a>

            <?php if ($usuario === null): ?>
                <a class="boton boton--secundario" href="<?= e(url('login')) ?>">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="portada__panel">
        <p class="tarjeta__eyebrow">Estado del sistema</p>
        <h2 class="tarjeta__titulo">Base de datos conectada</h2>

        <ul class="lista-datos">
            <li><span>Libros registrados</span><strong><?= e((string) $resumen['libros']) ?></strong></li>
            <li><span>Categorías</span><strong><?= e((string) $resumen['categorias']) ?></strong></li>
            <li><span>Usuarios</span><strong><?= e((string) $resumen['usuarios']) ?></strong></li>
        </ul>

        <p class="nota">
            Las cifras se obtienen en tiempo real de la base de datos
            <strong>Bibliotec</strong>.
        </p>
    </div>
</section>
