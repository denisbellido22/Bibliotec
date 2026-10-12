<?php
/**
 * BiblioTech — Formulario de categoría (alta y edición).
 *
 * Variables: $accion, $datos, $errores, $id, $categoria, $totalLibros.
 */

declare(strict_types=1);

$esEdicion  = $accion === 'editar';
$rutaAccion = $esEdicion ? 'admin/categorias/editar' : 'admin/categorias/crear';
$parametros = $esEdicion ? ['id' => (int) $id] : [];
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo"><?= $esEdicion ? 'Editar categoría' : 'Nueva categoría' ?></h1>
        <p class="encabezado__subtitulo">
            <?= $esEdicion
                ? 'Actualiza el nombre o la descripción de la categoría.'
                : 'Las categorías organizan el catálogo por área temática.' ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('admin/categorias')) ?>">Volver al listado</a>
    </div>
</div>

<?php if ($esEdicion && isset($totalLibros)): ?>
    <div class="mensaje mensaje--info">
        Esta categoría clasifica actualmente <?= e((string) $totalLibros) ?> libro(s).
    </div>
<?php endif; ?>

<form class="formulario tarjeta" method="post" action="<?= e(url($rutaAccion, $parametros)) ?>"
      data-aviso-cambios data-unico-envio>
    <?= csrf_field() ?>

    <div class="campo">
        <label class="campo__etiqueta" for="nombre">Nombre <span aria-hidden="true">*</span></label>
        <input class="campo__control <?= isset($errores['nombre']) ? 'campo__control--error' : '' ?>"
               type="text" id="nombre" name="nombre" maxlength="100" required
               value="<?= e((string) $datos['nombre']) ?>" placeholder="Ej.: Ingeniería de Software">
        <?php if (isset($errores['nombre'])): ?>
            <span class="campo__error"><?= e($errores['nombre']) ?></span>
        <?php endif; ?>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="descripcion">Descripción</label>
        <textarea class="campo__control <?= isset($errores['descripcion']) ? 'campo__control--error' : '' ?>"
                  id="descripcion" name="descripcion" rows="4"
                  placeholder="¿Qué tipo de libros agrupa esta categoría?"><?= e((string) $datos['descripcion']) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
            <span class="campo__error"><?= e($errores['descripcion']) ?></span>
        <?php endif; ?>
    </div>

    <div class="formulario__acciones">
        <button class="boton boton--primario" type="submit">
            <?= $esEdicion ? 'Guardar cambios' : 'Registrar categoría' ?>
        </button>
        <a class="boton boton--plano" href="<?= e(url('admin/categorias')) ?>">Cancelar</a>
    </div>
</form>
