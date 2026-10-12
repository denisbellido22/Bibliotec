<?php
/**
 * BiblioTech — Listado de multas (administrador y bibliotecario).
 *
 * Variables: $multas, $filtros, $resumen, $prefijo.
 */

declare(strict_types=1);
?>
<p class="saludo">¡Bienvenido, <strong><?= e((string) usuario_actual()['nombre']) ?></strong>!</p>

<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Multas</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) $resumen['pendientes']) ?> pendientes ·
            <?= e(dinero($resumen['monto_pendiente'])) ?> por cobrar ·
            <?= e(dinero($resumen['monto_cobrado'])) ?> cobrado
        </p>
    </div>
</div>

<section class="estadisticas">
    <div class="rejilla">
        <article class="estadistica">
            <p class="estadistica__nombre">Por cobrar</p>
            <p class="estadistica__valor estadistica__valor--dinero"><?= e(dinero($resumen['monto_pendiente'])) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['pendientes']) ?> multa(s) pendiente(s)</p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Cobrado</p>
            <p class="estadistica__valor estadistica__valor--ok"><?= e(dinero($resumen['monto_cobrado'])) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['pagadas']) ?> multa(s) pagada(s)</p>
        </article>

        <article class="estadistica">
            <p class="estadistica__nombre">Condonadas</p>
            <p class="estadistica__valor"><?= e((string) $resumen['condonadas']) ?></p>
            <p class="estadistica__nota"><?= e((string) $resumen['total']) ?> multas en total</p>
        </article>
    </div>
</section>

<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="<?= e($prefijo . '/multas') ?>">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por usuario, correo o libro</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="estado">Estado</label>
            <select class="campo__control" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (Multa::ESTADOS as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                        <?= e(ucfirst($estado)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url($prefijo . '/multas')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($multas === []): ?>
    <div class="vacio">
        <h3>No hay multas registradas</h3>
        <p>Las multas se generan automáticamente al registrar devoluciones con retraso.</p>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Libro y préstamo</th>
                    <th scope="col">Retraso</th>
                    <th scope="col">Monto</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($multas as $multa): ?>
                    <tr>
                        <td>#<?= e((string) $multa['id']) ?></td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $multa['usuario']) ?></span>
                            <span class="tabla__secundario"><?= e((string) $multa['usuario_email']) ?></span>
                        </td>
                        <td>
                            <span class="tabla__principal"><?= e((string) $multa['libro']) ?></span>
                            <span class="tabla__secundario">Préstamo #<?= e((string) $multa['id_prestamo']) ?></span>
                        </td>
                        <td>
                            <?= e((string) max(0, (int) $multa['dias_retraso'])) ?> día(s)
                            <span class="tabla__secundario">
                                Esperada: <?= e(fecha_corta((string) $multa['fecha_devolucion_esperada'])) ?>
                            </span>
                        </td>
                        <td><strong><?= e(dinero((float) $multa['monto'])) ?></strong></td>
                        <td>
                            <span class="<?= e(clase_badge((string) $multa['estado'])) ?>">
                                <?= e(ucfirst((string) $multa['estado'])) ?>
                            </span>
                            <span class="tabla__secundario">
                                Generada: <?= e(fecha_hora((string) $multa['fecha_generacion'])) ?>
                                <?php if (!empty($multa['fecha_pago'])): ?>
                                    · Pagada: <?= e(fecha_hora((string) $multa['fecha_pago'])) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="tabla__numero">
                            <?php if ($multa['estado'] === 'pendiente'): ?>
                                <div class="tabla__acciones">
                                    <form method="post"
                                          action="<?= e(url($prefijo . '/multas/pagar', ['id' => (int) $multa['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--exito" type="submit"
                                                data-confirmar="¿Registrar el pago de <?= e(dinero((float) $multa['monto'])) ?>?">
                                            Registrar pago
                                        </button>
                                    </form>

                                    <form method="post"
                                          action="<?= e(url($prefijo . '/multas/condonar', ['id' => (int) $multa['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <button class="boton boton--pequeno boton--plano" type="submit"
                                                data-confirmar="¿Condonar esta multa sin registrar cobro?">
                                            Condonar
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="tabla__secundario">Sin acciones</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
