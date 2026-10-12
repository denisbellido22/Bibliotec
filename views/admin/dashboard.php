<?php
/**
 * BiblioTech — Dashboard del administrador.
 *
 * Variables: $indicadores, $catalogo, $usuarios, $categorias, $serie, $estadoCatalogo.
 */

declare(strict_types=1);

$usuarioPanel = usuario_actual();
$maxSerie     = max(1, max(array_column($serie, 'total')));
?>
<p class="saludo">
    ¡Bienvenido, <strong><?= e((string) $usuarioPanel['nombre']) ?></strong>!
</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Reporte general</h1>
        <p class="encabezado__subtitulo">
            Estado actual de <?= e((string) config('app.nombre')) ?> · <?= e(date('d/m/Y')) ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <?php if (ruta_existe('admin/reportes')): ?>
            <a class="boton boton--plano" href="<?= e(url('admin/reportes')) ?>">Ver reportes</a>
        <?php endif; ?>

        <?php if (ruta_existe('admin/libros/crear')): ?>
            <a class="boton boton--primario" href="<?= e(url('admin/libros/crear')) ?>">Registrar libro</a>
        <?php endif; ?>
    </div>
</div>

<?php /* Tarjetas de indicadores (RF02, RF07, RF13) */ ?>
<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica surgir">
            <p class="estadistica__nombre">Catálogo</p>
            <p class="estadistica__valor"><?= e((string) $catalogo['titulos']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $catalogo['ejemplares']) ?> ejemplares ·
                <?= e((string) $catalogo['disponibles']) ?> disponibles ·
                <?= e((string) $categorias) ?> categorías
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Préstamos activos</p>
            <p class="estadistica__valor"><?= e((string) $indicadores['prestamos_activos']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $indicadores['prestamos_vencidos']) ?> vencidos ·
                <?= e((string) $indicadores['prestamos_total']) ?> históricos
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Reservas activas</p>
            <p class="estadistica__valor"><?= e((string) $indicadores['reservas_pendientes']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $indicadores['reservas_confirmadas']) ?> confirmadas ·
                <?= e((string) $indicadores['reservas_total']) ?> registradas
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Multas pendientes</p>
            <p class="estadistica__valor estadistica__valor--dinero">
                <?= e(dinero((float) $indicadores['multas_monto_pendiente'])) ?>
            </p>
            <p class="estadistica__nota">
                <?= e((string) $indicadores['multas_pendientes']) ?> registros por cobrar
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Estudiantes</p>
            <p class="estadistica__valor"><?= e((string) $usuarios['estudiantes']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $usuarios['activos']) ?> usuarios activos ·
                <?= e((string) $usuarios['bloqueados']) ?> bloqueados
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Bibliotecarios</p>
            <p class="estadistica__valor"><?= e((string) $usuarios['bibliotecarios']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $usuarios['administradores']) ?> administradores ·
                <?= e((string) $usuarios['total']) ?> usuarios en total
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Préstamos este mes</p>
            <p class="estadistica__valor"><?= e((string) $indicadores['prestamos_mes']) ?></p>
            <p class="estadistica__nota"><?= e(mes_largo(date('Y-m-01'))) ?></p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Cobrado este mes</p>
            <p class="estadistica__valor estadistica__valor--ok">
                <?= e(dinero((float) $indicadores['multas_cobrado_mes'])) ?>
            </p>
            <p class="estadistica__nota">
                <?= e((string) $indicadores['multas_total']) ?> multas registradas
            </p>
        </article>
    </div>
</section>

<div class="rejilla rejilla--dos">
    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Actividad</p>
        <h2 class="tarjeta__titulo">Préstamos últimos 6 meses</h2>

        <div class="grafico" role="img"
             aria-label="Préstamos registrados en los últimos seis meses">
            <?php foreach ($serie as $punto): ?>
                <?php $altura = (int) round(((int) $punto['total'] / $maxSerie) * 100); ?>
                <div class="grafico__barra">
                    <span class="grafico__valor"><?= e((string) $punto['total']) ?></span>
                    <span class="grafico__columna" style="height: <?= e((string) max(4, $altura)) ?>%"></span>
                    <span class="grafico__etiqueta"><?= e((string) $punto['etiqueta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Disponibilidad</p>
        <h2 class="tarjeta__titulo">Estado del catálogo</h2>

        <ul class="lista-datos">
            <li>
                <span>Títulos con todos los ejemplares</span>
                <strong><?= e((string) $estadoCatalogo['disponibles']) ?></strong>
            </li>
            <li>
                <span>Títulos con ejemplares prestados</span>
                <strong><?= e((string) $estadoCatalogo['parciales']) ?></strong>
            </li>
            <li>
                <span>Títulos agotados</span>
                <strong><?= e((string) $estadoCatalogo['agotados']) ?></strong>
            </li>
            <li>
                <span>Ejemplares en préstamo</span>
                <strong><?= e((string) $catalogo['prestados']) ?></strong>
            </li>
        </ul>

        <div class="encabezado__acciones">
            <?php if (ruta_existe('admin/libros')): ?>
                <a class="boton boton--plano" href="<?= e(url('admin/libros')) ?>">Gestionar libros</a>
            <?php endif; ?>

            <?php if (ruta_existe('admin/usuarios')): ?>
                <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Gestionar usuarios</a>
            <?php endif; ?>
        </div>
    </section>
</div>
