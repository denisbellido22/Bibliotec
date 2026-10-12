<?php
/**
 * BiblioTech — Reportes y estadísticas.
 *
 * Variables: $meses, $indicadores, $catalogo, $usuarios, $multas, $prestamos,
 *            $reservas, $seriePrestamos, $serieCobros, $porCategoria,
 *            $masPrestados, $catalogoCategoria, $estadoCatalogo.
 */

declare(strict_types=1);

$maxPrestamos = max(1, max(array_column($seriePrestamos, 'total')));
$maxCobros    = max(0.01, max(array_column($serieCobros, 'total')));
$totalPrestamosCategoria = max(1, array_sum(array_column($porCategoria, 'total')));
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Reportes y estadísticas</h1>
        <p class="encabezado__subtitulo">
            Datos reales de la base de datos Bibliotec · actualizado el <?= e(date('d/m/Y H:i')) ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <?php foreach ([3 => '3 meses', 6 => '6 meses', 12 => '12 meses'] as $valor => $etiqueta): ?>
            <a class="boton <?= $meses === $valor ? 'boton--primario' : 'boton--plano' ?>"
               href="<?= e(url('admin/reportes', ['meses' => $valor])) ?>"><?= e($etiqueta) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php /* Indicadores principales */ ?>
<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica">
            <p class="estadistica__nombre">Títulos en catálogo</p>
            <p class="estadistica__valor"><?= e((string) $catalogo['titulos']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $catalogo['ejemplares']) ?> ejemplares ·
                <?= e((string) $catalogo['agotados']) ?> títulos agotados
            </p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Préstamos históricos</p>
            <p class="estadistica__valor"><?= e((string) $prestamos['total']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $prestamos['activos']) ?> abiertos ·
                <?= e((string) $prestamos['vencidos']) ?> vencidos
            </p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Usuarios registrados</p>
            <p class="estadistica__valor"><?= e((string) $usuarios['total']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $usuarios['estudiantes']) ?> estudiantes ·
                <?= e((string) $usuarios['bibliotecarios']) ?> bibliotecarios
            </p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Recaudado por multas</p>
            <p class="estadistica__valor estadistica__valor--ok"><?= e(dinero($multas['monto_cobrado'])) ?></p>
            <p class="estadistica__nota">
                <?= e(dinero($multas['monto_pendiente'])) ?> pendiente ·
                <?= e((string) $multas['condonadas']) ?> condonadas
            </p>
        </article>
    </div>
</section>

<div class="rejilla rejilla--dos">
    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Actividad</p>
        <h2 class="tarjeta__titulo">Préstamos por mes</h2>

        <div class="grafico" role="img" aria-label="Préstamos registrados por mes">
            <?php foreach ($seriePrestamos as $punto): ?>
                <?php $altura = (int) round(((int) $punto['total'] / $maxPrestamos) * 100); ?>
                <div class="grafico__barra">
                    <span class="grafico__valor"><?= e((string) $punto['total']) ?></span>
                    <span class="grafico__columna" style="height: <?= e((string) max(4, $altura)) ?>%"></span>
                    <span class="grafico__etiqueta"><?= e((string) $punto['etiqueta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Recaudación</p>
        <h2 class="tarjeta__titulo">Multas cobradas por mes</h2>

        <div class="grafico" role="img" aria-label="Importe cobrado por multas cada mes">
            <?php foreach ($serieCobros as $punto): ?>
                <?php $altura = (int) round((((float) $punto['total']) / $maxCobros) * 100); ?>
                <div class="grafico__barra">
                    <span class="grafico__valor"><?= e(number_format((float) $punto['total'], 0)) ?></span>
                    <span class="grafico__columna grafico__columna--dinero"
                          style="height: <?= e((string) max(4, $altura)) ?>%"></span>
                    <span class="grafico__etiqueta"><?= e((string) $punto['etiqueta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="nota" style="margin-top:.6rem">Importes en soles peruanos.</p>
    </section>
</div>

<div class="rejilla rejilla--dos" style="margin-top:1.2rem">
    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Interés por área</p>
        <h2 class="tarjeta__titulo">Préstamos por categoría</h2>

        <?php if ($porCategoria === []): ?>
            <p class="nota">Todavía no hay préstamos registrados para analizar.</p>
        <?php else: ?>
            <ul class="barras">
                <?php foreach ($porCategoria as $fila): ?>
                    <?php $porcentaje = (int) round(((int) $fila['total'] / $totalPrestamosCategoria) * 100); ?>
                    <li class="barras__fila">
                        <span class="barras__etiqueta"><?= e((string) $fila['categoria']) ?></span>
                        <span class="barras__pista">
                            <span class="barras__relleno" style="width: <?= e((string) max(2, $porcentaje)) ?>%"></span>
                        </span>
                        <span class="barras__valor"><?= e((string) $fila['total']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="tarjeta">
        <p class="tarjeta__eyebrow">Demanda</p>
        <h2 class="tarjeta__titulo">Libros más prestados</h2>

        <?php if ($masPrestados === []): ?>
            <p class="nota">Sin préstamos registrados todavía.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Libro</th>
                            <th scope="col">Autor</th>
                            <th scope="col" class="tabla__numero">Préstamos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($masPrestados as $libro): ?>
                            <tr>
                                <td>
                                    <span class="tabla__principal"><?= e((string) $libro['titulo']) ?></span>
                                </td>
                                <td><?= e((string) $libro['autor']) ?></td>
                                <td class="tabla__numero">
                                    <span class="badge badge--info"><?= e((string) $libro['total_prestamos']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="tarjeta" style="margin-top:1.2rem">
    <p class="tarjeta__eyebrow">Colección</p>
    <h2 class="tarjeta__titulo">Catálogo y disponibilidad por categoría</h2>

    <div class="tabla-envoltura" style="margin-top:.9rem">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Categoría</th>
                    <th scope="col">Títulos</th>
                    <th scope="col">Ejemplares</th>
                    <th scope="col">Disponibles</th>
                    <th scope="col">Ocupación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalogoCategoria as $fila): ?>
                    <?php
                    $ejemplares  = (int) $fila['ejemplares'];
                    $disponibles = (int) $fila['disponibles'];
                    $ocupacion   = $ejemplares > 0 ? (int) round((($ejemplares - $disponibles) / $ejemplares) * 100) : 0;
                    ?>
                    <tr>
                        <td><span class="tabla__principal"><?= e((string) $fila['categoria']) ?></span></td>
                        <td><?= e((string) $fila['titulos']) ?></td>
                        <td><?= e((string) $ejemplares) ?></td>
                        <td><?= e((string) $disponibles) ?></td>
                        <td>
                            <span class="badge <?= $ocupacion > 60 ? 'badge--alerta' : 'badge--ok' ?>">
                                <?= e((string) $ocupacion) ?>% en préstamo
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="nota" style="margin-top:.8rem">
        Estado global del catálogo:
        <?= e((string) $estadoCatalogo['disponibles']) ?> títulos completos ·
        <?= e((string) $estadoCatalogo['parciales']) ?> con ejemplares prestados ·
        <?= e((string) $estadoCatalogo['agotados']) ?> agotados.
        Reservas activas: <?= e((string) $reservas['pendientes']) ?> pendientes de
        <?= e((string) $reservas['total']) ?> registradas.
    </p>
</section>
