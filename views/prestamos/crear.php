<?php
/**
 * BiblioTech — Registro de un préstamo.
 *
 * Variables: $datos, $errores, $usuarios, $libros, $prefijo, $diasPrestamo.
 */

declare(strict_types=1);
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Registrar préstamo</h1>
        <p class="encabezado__subtitulo">
            El stock disponible se descuenta automáticamente al confirmar el préstamo.
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url($prefijo . '/prestamos')) ?>">Volver al listado</a>
    </div>
</div>

<?php if (!empty($errores['general'])): ?>
    <div class="mensaje mensaje--error" role="alert"><?= e($errores['general']) ?></div>
<?php endif; ?>

<form class="formulario tarjeta" method="post"
      action="<?= e(url($prefijo . '/prestamos/crear')) ?>" data-unico-envio>
    <?= csrf_field() ?>

    <div class="formulario__rejilla">
        <div class="campo">
            <label class="campo__etiqueta" for="id_usuario">Usuario <span aria-hidden="true">*</span></label>
            <select class="campo__control <?= isset($errores['id_usuario']) ? 'campo__control--error' : '' ?>"
                    id="id_usuario" name="id_usuario" required>
                <option value="">Selecciona un usuario</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?= e((string) $usuario['id']) ?>"
                        <?= (int) $datos['id_usuario'] === (int) $usuario['id'] ? 'selected' : '' ?>>
                        <?= e((string) $usuario['nombre']) ?> — <?= e(nombre_rol((string) $usuario['rol'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['id_usuario'])): ?>
                <span class="campo__error"><?= e($errores['id_usuario']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="id_libro">Libro <span aria-hidden="true">*</span></label>
            <select class="campo__control <?= isset($errores['id_libro']) ? 'campo__control--error' : '' ?>"
                    id="id_libro" name="id_libro" required>
                <option value="">Selecciona un libro con ejemplares</option>
                <?php foreach ($libros as $libro): ?>
                    <option value="<?= e((string) $libro['id']) ?>"
                        <?= (int) $datos['id_libro'] === (int) $libro['id'] ? 'selected' : '' ?>>
                        <?= e((string) $libro['titulo']) ?>
                        — disponibles: <?= e((string) $libro['stock_disponible']) ?>/<?= e((string) $libro['stock_total']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo__ayuda">
                Sólo se listan libros con ejemplares disponibles. Si el usuario tiene una reserva
                pendiente de ese título, se vincula automáticamente.
            </span>
            <?php if (isset($errores['id_libro'])): ?>
                <span class="campo__error"><?= e($errores['id_libro']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="fecha_prestamo">Fecha de préstamo <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['fecha_prestamo']) ? 'campo__control--error' : '' ?>"
                   type="date" id="fecha_prestamo" name="fecha_prestamo" required
                   value="<?= e((string) $datos['fecha_prestamo']) ?>">
            <?php if (isset($errores['fecha_prestamo'])): ?>
                <span class="campo__error"><?= e($errores['fecha_prestamo']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="fecha_devolucion_esperada">
                Devolución esperada <span aria-hidden="true">*</span>
            </label>
            <input class="campo__control <?= isset($errores['fecha_devolucion_esperada']) ? 'campo__control--error' : '' ?>"
                   type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada" required
                   value="<?= e((string) $datos['fecha_devolucion_esperada']) ?>">
            <span class="campo__ayuda">
                Plazo estándar: <?= e((string) $diasPrestamo) ?> días.
                Multa por retraso: <?= e(dinero((float) config('prestamos.multa_por_dia'))) ?> por día.
            </span>
            <?php if (isset($errores['fecha_devolucion_esperada'])): ?>
                <span class="campo__error"><?= e($errores['fecha_devolucion_esperada']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="formulario__acciones">
        <button class="boton boton--primario" type="submit">Registrar préstamo</button>
        <a class="boton boton--plano" href="<?= e(url($prefijo . '/prestamos')) ?>">Cancelar</a>
    </div>
</form>
