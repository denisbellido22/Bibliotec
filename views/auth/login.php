<?php
/**
 * BiblioTech — Formulario de inicio de sesión.
 *
 * Variables: $errores, $datos.
 */

declare(strict_types=1);

$destino = (string) entrada('destino', '');
?>
<section class="tarjeta acceso__tarjeta surgir">
    <h1 class="acceso__titulo">Iniciar sesión</h1>
    <p class="nota">Accede con las credenciales asignadas a tu rol.</p>

    <?php if (!empty($errores['general'])): ?>
        <div class="mensaje mensaje--error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= e(url('login')) ?>" data-unico-envio>
        <?= csrf_field() ?>

        <?php if ($destino !== ''): ?>
            <input type="hidden" name="destino" value="<?= e($destino) ?>">
        <?php endif; ?>

        <div class="campo">
            <label class="campo__etiqueta" for="email">Correo electrónico</label>
            <input class="campo__control <?= isset($errores['email']) ? 'campo__control--error' : '' ?>"
                   type="email" id="email" name="email" required autofocus
                   autocomplete="username"
                   value="<?= e((string) ($datos['email'] ?? '')) ?>"
                   placeholder="usuario@bibliotech.test">
            <?php if (isset($errores['email'])): ?>
                <span class="campo__error"><?= e($errores['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__etiqueta" for="password">Contraseña</label>
            <input class="campo__control <?= isset($errores['password']) ? 'campo__control--error' : '' ?>"
                   type="password" id="password" name="password" required
                   autocomplete="current-password" placeholder="••••••••">
            <?php if (isset($errores['password'])): ?>
                <span class="campo__error"><?= e($errores['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="formulario__acciones">
            <button class="boton boton--primario boton--bloque" type="submit">Ingresar</button>
        </div>
    </form>

    <details class="credenciales-demo">
        <summary>Credenciales de demostración</summary>
        <ul class="lista-datos">
            <li><span>Administrador</span><strong>admin@bibliotech.test</strong></li>
            <li><span>Bibliotecario</span><strong>biblio@bibliotech.test</strong></li>
            <li><span>Estudiante</span><strong>carlos@bibliotech.test</strong></li>
            <li><span>Contraseña</span><strong>password</strong></li>
        </ul>
        <p class="nota">
            Los usuarios existentes conservan sus contraseñas: los hashes almacenados
            son compatibles con <code>password_verify()</code>.
        </p>
    </details>
</section>
