<?php
/**
 * BiblioTech — Formulario de libro (alta y edición).
 *
 * Variables: $accion ('crear'|'editar'), $datos, $errores, $categorias, $id, $libro.
 */

declare(strict_types=1);

$esEdicion = $accion === 'editar';
$rutaAccion = $esEdicion ? 'admin/libros/editar' : 'admin/libros/crear';
$parametros = $esEdicion ? ['id' => (int) $id] : [];
$portadaActual = $esEdicion ? ($libro['portada_url'] ?? null) : null;
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo"><?= $esEdicion ? 'Editar libro' : 'Registrar libro' ?></h1>
        <p class="encabezado__subtitulo">
            <?= $esEdicion
                ? 'Actualiza la información del título seleccionado.'
                : 'Completa los datos del nuevo título del catálogo.' ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('admin/libros')) ?>">Volver al listado</a>
    </div>
</div>

<?php if ($errores !== []): ?>
    <div class="mensaje mensaje--error" role="alert">
        Revisa los campos marcados: hay <?= e((string) count($errores)) ?>
        dato(s) por corregir.
    </div>
<?php endif; ?>

<form class="formulario tarjeta" method="post"
      action="<?= e(url($rutaAccion, $parametros)) ?>"
      enctype="multipart/form-data" data-aviso-cambios data-unico-envio>

    <?= csrf_field() ?>

    <div class="formulario__rejilla">
        <div class="campo campo--ancho">
            <label class="campo__etiqueta" for="titulo">Título <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['titulo']) ? 'campo__control--error' : '' ?>"
                   type="text" id="titulo" name="titulo" maxlength="255" required
                   value="<?= e((string) $datos['titulo']) ?>">
            <?php if (isset($errores['titulo'])): ?>
                <span class="campo__error"><?= e($errores['titulo']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="autor">Autor <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['autor']) ? 'campo__control--error' : '' ?>"
                   type="text" id="autor" name="autor" maxlength="180" required
                   value="<?= e((string) $datos['autor']) ?>">
            <?php if (isset($errores['autor'])): ?>
                <span class="campo__error"><?= e($errores['autor']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="isbn">ISBN</label>
            <input class="campo__control <?= isset($errores['isbn']) ? 'campo__control--error' : '' ?>"
                   type="text" id="isbn" name="isbn" maxlength="20" inputmode="numeric"
                   value="<?= e((string) $datos['isbn']) ?>" placeholder="9780132350884">
            <span class="campo__ayuda">10 o 13 dígitos. Se admiten guiones.</span>
            <?php if (isset($errores['isbn'])): ?>
                <span class="campo__error"><?= e($errores['isbn']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="id_categoria">Categoría</label>
            <select class="campo__control <?= isset($errores['id_categoria']) ? 'campo__control--error' : '' ?>"
                    id="id_categoria" name="id_categoria">
                <option value="0">Sin categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= e((string) $categoria['id']) ?>"
                        <?= (int) $datos['id_categoria'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                        <?= e((string) $categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['id_categoria'])): ?>
                <span class="campo__error"><?= e($errores['id_categoria']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="editorial">Editorial</label>
            <input class="campo__control <?= isset($errores['editorial']) ? 'campo__control--error' : '' ?>"
                   type="text" id="editorial" name="editorial" maxlength="150"
                   value="<?= e((string) $datos['editorial']) ?>">
            <?php if (isset($errores['editorial'])): ?>
                <span class="campo__error"><?= e($errores['editorial']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="anio">Año de publicación</label>
            <input class="campo__control <?= isset($errores['anio']) ? 'campo__control--error' : '' ?>"
                   type="number" id="anio" name="anio" min="1400" max="<?= e((string) ((int) date('Y') + 1)) ?>"
                   value="<?= e($datos['anio'] !== null ? (string) $datos['anio'] : '') ?>">
            <?php if (isset($errores['anio'])): ?>
                <span class="campo__error"><?= e($errores['anio']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="stock_total">Stock total <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['stock_total']) ? 'campo__control--error' : '' ?>"
                   type="number" id="stock_total" name="stock_total" min="0" required
                   value="<?= e((string) $datos['stock_total']) ?>">
            <span class="campo__ayuda">Cantidad total de ejemplares de la biblioteca.</span>
            <?php if (isset($errores['stock_total'])): ?>
                <span class="campo__error"><?= e($errores['stock_total']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="stock_disponible">Ejemplares disponibles</label>
            <input class="campo__control <?= isset($errores['stock_disponible']) ? 'campo__control--error' : '' ?>"
                   type="number" id="stock_disponible" name="stock_disponible" min="0"
                   value="<?= e((string) $datos['stock_disponible']) ?>">
            <span class="campo__ayuda">No puede superar el stock total.</span>
            <?php if (isset($errores['stock_disponible'])): ?>
                <span class="campo__error"><?= e($errores['stock_disponible']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo campo--ancho">
            <label class="campo__etiqueta" for="sinopsis">Sinopsis</label>
            <textarea class="campo__control" id="sinopsis" name="sinopsis" rows="4"
                      placeholder="Breve descripción del contenido del libro."><?= e((string) $datos['sinopsis']) ?></textarea>
        </div>
    </div>

    <?php /* Portada: carga desde el explorador de archivos + vista previa */ ?>
    <fieldset class="grupo-portada">
        <legend class="campo__etiqueta">Portada del libro</legend>

        <div class="grupo-portada__cuerpo">
            <img class="grupo-portada__vista"
                 data-vista-previa
                 src="<?= e(portada_url($portadaActual)) ?>"
                 alt="Vista previa de la portada">

            <div class="grupo-portada__controles">
                <div class="campo">
                    <label class="campo__etiqueta" for="portada">Seleccionar imagen</label>
                    <input class="campo__control" type="file" id="portada" name="portada"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-vista-previa-archivo>
                    <span class="campo__ayuda">
                        JPG, PNG, WEBP o GIF. Máximo <?= e((string) round((int) config('uploads.max_bytes') / 1048576, 1)) ?> MB.
                        <span data-nombre-archivo></span>
                    </span>
                    <?php if (isset($errores['portada'])): ?>
                        <span class="campo__error"><?= e($errores['portada']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label class="campo__etiqueta" for="portada_url">O pega una dirección web</label>
                    <input class="campo__control <?= isset($errores['portada_url']) ? 'campo__control--error' : '' ?>"
                           type="url" id="portada_url" name="portada_url"
                           value="<?= e((string) $datos['portada_url']) ?>"
                           placeholder="https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg">
                    <span class="campo__ayuda">Si subes un archivo, tendrá prioridad sobre esta dirección.</span>
                    <?php if (isset($errores['portada_url'])): ?>
                        <span class="campo__error"><?= e($errores['portada_url']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </fieldset>

    <div class="formulario__acciones">
        <button class="boton boton--primario" type="submit">
            <?= $esEdicion ? 'Guardar cambios' : 'Registrar libro' ?>
        </button>
        <a class="boton boton--plano" href="<?= e(url('admin/libros')) ?>">Cancelar</a>
    </div>
</form>
