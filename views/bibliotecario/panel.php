<?php
/**
 * BiblioTech — Panel del bibliotecario.
 *
 * Variables: $catalogo, $prestamos, $reservas, $multas, $vencidos.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Panel de biblioteca</h1>
        <p class="encabezado__subtitulo">
            Operaciones del día · <?= e(date('d/m/Y')) ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('bibliotecario/libros')) ?>">Consultar catálogo</a>
        <a class="boton boton--primario" href="<?= e(url('bibliotecario/prestamos/crear')) ?>">Registrar préstamo</a>
    </div>
</div>

<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica surgir">
            <p class="estadistica__nombre">Ejemplares disponibles</p>
            <p class="estadistica__valor"><?= e((string) $catalogo['disponibles']) ?></p>
            <p class="estadistica__nota">
                de <?= e((string) $catalogo['ejemplares']) ?> ejemplares ·
                <?= e((string) $catalogo['titulos']) ?> títulos
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Préstamos abiertos</p>
            <p class="estadistica__valor"><?= e((string) $prestamos['activos']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $prestamos['vencidos']) ?> vencidos ·
                <?= e((string) $prestamos['del_mes']) ?> este mes
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Reservas por atender</p>
            <p class="estadistica__valor"><?= e((string) $reservas['pendientes']) ?></p>
            <p class="estadistica__nota">
                <?= e((string) $reservas['confirmadas']) ?> confirmadas ·
                <?= e((string) $reservas['total']) ?> registradas
            </p>
        </article>

        <article class="estadistica surgir">
            <p class="estadistica__nombre">Multas por cobrar</p>
            <p class="estadistica__valor estadistica__valor--dinero">
                <?= e(dinero($multas['monto_pendiente'])) ?>
            </p>
            <p class="estadistica__nota">
                <?= e((string) $multas['pendientes']) ?> pendientes ·
                <?= e(dinero($multas['monto_cobrado'])) ?> cobrado
            </p>
        </article>
    </div>
</section>

<?php if ($catalogo['agotados'] > 0): ?>
    <div class="mensaje mensaje--aviso">
        Hay <?= e((string) $catalogo['agotados']) ?> título(s) sin ejemplares disponibles.
        Considera solicitar reposición al administrador.
    </div>
<?php endif; ?>

<section class="tarjeta">
    <p class="tarjeta__eyebrow">Seguimiento</p>
    <h2 class="tarjeta__titulo">Préstamos con retraso</h2>

    <?php if ($vencidos === []): ?>
        <p class="nota">No hay préstamos vencidos. Todas las devoluciones están al día.</p>
    <?php else: ?>
        <div class="tabla-envoltura" style="margin-top:.8rem">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Libro</th>
                        <th scope="col">Devolución esperada</th>
                        <th scope="col">Retraso</th>
                        <th scope="col" class="tabla__numero">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vencidos as $prestamo): ?>
                        <tr>
                            <td>#<?= e((string) $prestamo['id']) ?></td>
                            <td>
                                <span class="tabla__principal"><?= e((string) $prestamo['usuario']) ?></span>
                                <span class="tabla__secundario"><?= e((string) $prestamo['usuario_email']) ?></span>
                            </td>
                            <td><?= e((string) $prestamo['libro']) ?></td>
                            <td><?= e(fecha_corta((string) $prestamo['fecha_devolucion_esperada'])) ?></td>
                            <td>
                                <span class="badge badge--peligro">
                                    <?= e((string) max(0, (int) $prestamo['dias_retraso'])) ?> día(s)
                                </span>
                            </td>
                            <td class="tabla__numero">
                                <form method="post"
                                      action="<?= e(url('bibliotecario/prestamos/devolver', ['id' => (int) $prestamo['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--pequeno boton--exito" type="submit"
                                            data-confirmar="¿Registrar la devolución de «<?= e((string) $prestamo['libro']) ?>»?">
                                        Registrar devolución
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="encabezado__acciones" style="margin-top:1rem">
        <a class="boton boton--plano" href="<?= e(url('bibliotecario/prestamos')) ?>">Ver todos los préstamos</a>
        <a class="boton boton--plano" href="<?= e(url('bibliotecario/reservas')) ?>">Ver reservas</a>
        <a class="boton boton--plano" href="<?= e(url('bibliotecario/multas')) ?>">Ver multas</a>
    </div>
</section>
