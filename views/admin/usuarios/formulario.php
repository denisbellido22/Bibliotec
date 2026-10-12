<?php
/**
 * BiblioTech — Formulario de usuario (alta y edición).
 *
 * Variables: $accion, $datos, $errores, $id, $usuario.
 */

declare(strict_types=1);

$esEdicion  = $accion === 'editar';
$rutaAccion = $esEdicion ? 'admin/usuarios/editar' : 'admin/usuarios/crear';
$parametros = $esEdicion ? ['id' => (int) $id] : [];
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo"><?= $esEdicion ? 'Editar usuario' : 'Registrar usuario' ?></h1>
        <p class="encabezado__subtitulo">
            <?= $esEdicion
                ? 'Modifica los datos, el rol o el estado de la cuenta.'
                : 'La contraseña se guarda cifrada con password_hash().' ?>
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Volver al listado</a>
    </div>
</div>

<form class="formulario tarjeta" method="post" action="<?= e(url($rutaAccion, $parametros)) ?>"
      data-aviso-cambios data-unico-envio>
    <?= csrf_field() ?>

    <div class="formulario__rejilla">
        <div class="campo">
            <label class="campo__etiqueta" for="nombre">Nombre completo <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['nombre']) ? 'campo__control--error' : '' ?>"
                   type="text" id="nombre" name="nombre" maxlength="120" required
                   value="<?= e((string) $datos['nombre']) ?>">
            <?php if (isset($errores['nombre'])): ?>
                <span class="campo__error"><?= e($errores['nombre']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="email">Correo electrónico <span aria-hidden="true">*</span></label>
            <input class="campo__control <?= isset($errores['email']) ? 'campo__control--error' : '' ?>"
                   type="email" id="email" name="email" maxlength="180" required
                   value="<?= e((string) $datos['email']) ?>" placeholder="usuario@bibliotech.test">
            <?php if (isset($errores['email'])): ?>
                <span class="campo__error"><?= e($errores['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="rol">Rol <span aria-hidden="true">*</span></label>
            <select class="campo__control <?= isset($errores['rol']) ? 'campo__control--error' : '' ?>"
                    id="rol" name="rol" required>
                <?php foreach (Usuario::ROLES as $rol): ?>
                    <option value="<?= e($rol) ?>" <?= $datos['rol'] === $rol ? 'selected' : '' ?>>
                        <?= e(nombre_rol($rol)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo__ayuda">Determina los módulos a los que puede acceder.</span>
            <?php if (isset($errores['rol'])): ?>
                <span class="campo__error"><?= e($errores['rol']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="estado">Estado <span aria-hidden="true">*</span></label>
            <select class="campo__control <?= isset($errores['estado']) ? 'campo__control--error' : '' ?>"
                    id="estado" name="estado" required>
                <?php foreach (Usuario::ESTADOS as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= $datos['estado'] === $estado ? 'selected' : '' ?>>
                        <?= e(nombre_estado_usuario($estado)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="campo__ayuda">Un usuario bloqueado no puede iniciar sesión.</span>
            <?php if (isset($errores['estado'])): ?>
                <span class="campo__error"><?= e($errores['estado']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo campo--ancho">
            <label class="campo__etiqueta" for="password">
                Contraseña <?= $esEdicion ? '(dejar vacío para conservarla)' : '*' ?>
            </label>
            <input class="campo__control <?= isset($errores['password']) ? 'campo__control--error' : '' ?>"
                   type="password" id="password" name="password"
                   autocomplete="new-password" minlength="8"
                   placeholder="Mínimo 8 caracteres"
                   <?= $esEdicion ? '' : 'required' ?>>
            <?php if (isset($errores['password'])): ?>
                <span class="campo__error"><?= e($errores['password']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="formulario__acciones">
        <button class="boton boton--primario" type="submit">
            <?= $esEdicion ? 'Guardar cambios' : 'Registrar usuario' ?>
        </button>
        <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Cancelar</a>
    </div>
</form>
