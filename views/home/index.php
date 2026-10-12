<?php
/**
 * BiblioTech — Página principal.
 *
 * Variables: $usuario, $resumen, $destacados, $categorias.
 */

declare(strict_types=1);

$hayDestacados = $destacados !== [];
?>
<section class="portada">
    <div class="portada__texto">
        <p class="etiqueta-superior">Biblioteca virtual</p>
        <h1 class="portada__titulo">Tu biblioteca, ordenada y siempre disponible</h1>
        <p class="portada__descripcion">
            Consulta el catálogo completo, revisa la disponibilidad de cada ejemplar y
            gestiona préstamos, reservas y multas desde un solo lugar.
        </p>

        <div class="portada__acciones">
            <a class="boton boton--primario" href="<?= e(url('catalogo')) ?>">Explorar el catálogo</a>

            <?php if ($usuario === null): ?>
                <a class="boton boton--secundario" href="<?= e(url('login')) ?>">Iniciar sesión</a>
            <?php else: ?>
                <a class="boton boton--secundario" href="<?= e(url('estudiante/panel')) ?>">Ir a mi cuenta</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="portada__panel">
        <p class="tarjeta__eyebrow">Estado del catálogo</p>
        <h2 class="tarjeta__titulo">Datos actuales del sistema</h2>

        <ul class="lista-datos">
            <li>
                <span>Títulos registrados</span>
                <strong><?= e((string) $resumen['titulos']) ?></strong>
            </li>
            <li>
                <span>Ejemplares totales</span>
                <strong><?= e((string) $resumen['ejemplares']) ?></strong>
            </li>
            <li>
                <span>Ejemplares disponibles</span>
                <strong><?= e((string) $resumen['disponibles']) ?></strong>
            </li>
            <li>
                <span>Categorías</span>
                <strong><?= e((string) count($categorias)) ?></strong>
            </li>
        </ul>

        <p class="nota">
            Información obtenida en tiempo real de la base de datos
            <strong>Bibliotec</strong>.
        </p>
    </div>
</section>

<?php if ($categorias !== []): ?>
    <section class="seccion">
        <div class="encabezado">
            <div>
                <h2 class="encabezado__titulo">Explora por categoría</h2>
                <p class="encabezado__subtitulo">Filtra el catálogo por el área que te interesa.</p>
            </div>
        </div>

        <div class="categorias">
            <?php foreach ($categorias as $categoria): ?>
                <a class="categoria-bloque surgir"
                   href="<?= e(url('catalogo', ['categoria' => (int) $categoria['id']])) ?>">
                    <span class="categoria-bloque__nombre"><?= e((string) $categoria['nombre']) ?></span>
                    <span class="categoria-bloque__total">
                        <?= e((string) $categoria['total_libros']) ?>
                        <?= (int) $categoria['total_libros'] === 1 ? 'título' : 'títulos' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($hayDestacados): ?>
    <section class="seccion">
        <div class="encabezado">
            <div>
                <h2 class="encabezado__titulo">Recién incorporados</h2>
                <p class="encabezado__subtitulo">Los últimos títulos agregados al catálogo.</p>
            </div>
            <div class="encabezado__acciones">
                <a class="boton boton--plano" href="<?= e(url('catalogo', ['orden' => 'reciente'])) ?>">
                    Ver todos
                </a>
            </div>
        </div>

        <div class="catalogo">
            <?php foreach ($destacados as $libro): ?>
                <?php require BASE_PATH . '/views/partials/libro-tarjeta.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="seccion">
    <div class="rejilla">
        <article class="tarjeta">
            <p class="tarjeta__eyebrow">Para estudiantes</p>
            <h3 class="tarjeta__titulo">Busca y reserva</h3>
            <p class="nota">
                Encuentra un libro por título, autor o ISBN, revisa si tiene ejemplares
                disponibles y consulta tus préstamos, reservas y multas.
            </p>
        </article>

        <article class="tarjeta">
            <p class="tarjeta__eyebrow">Para bibliotecarios</p>
            <h3 class="tarjeta__titulo">Préstamos y devoluciones</h3>
            <p class="nota">
                Registra préstamos verificando el stock disponible, controla los vencidos
                y registra devoluciones actualizando los ejemplares.
            </p>
        </article>

        <article class="tarjeta">
            <p class="tarjeta__eyebrow">Para administradores</p>
            <h3 class="tarjeta__titulo">Control total</h3>
            <p class="nota">
                Gestiona libros, categorías, usuarios y roles, y consulta reportes con la
                información real del sistema.
            </p>
        </article>
    </div>
</section>
