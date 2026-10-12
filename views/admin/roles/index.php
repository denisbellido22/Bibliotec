<?php
/**
 * BiblioTech — Roles y permisos.
 *
 * Variables: $matriz, $conteo, $usuarios.
 */

declare(strict_types=1);

$roles = Usuario::ROLES;
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Roles y permisos</h1>
        <p class="encabezado__subtitulo">
            Los permisos se verifican en el servidor antes de ejecutar cada módulo.
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Gestionar usuarios</a>
    </div>
</div>

<section class="estadisticas">
    <div class="rejilla">
        <?php foreach ($roles as $rol): ?>
            <article class="estadistica">
                <p class="estadistica__nombre"><?= e(nombre_rol($rol)) ?></p>
                <p class="estadistica__valor"><?= e((string) $conteo[$rol]['activos']) ?></p>
                <p class="estadistica__nota">
                    activo(s) · <?= e((string) $conteo[$rol]['bloqueados']) ?> bloqueado(s)
                </p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="tarjeta">
    <p class="tarjeta__eyebrow">Matriz de acceso</p>
    <h2 class="tarjeta__titulo">Módulos disponibles por rol</h2>

    <div class="tabla-envoltura" style="margin-top:1rem">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Módulo</th>
                    <th scope="col">Requerimiento</th>
                    <?php foreach ($roles as $rol): ?>
                        <th scope="col"><?= e(nombre_rol($rol)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matriz as $modulo => $detalle): ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $modulo) ?></span>
                            <span class="tabla__secundario"><?= e($detalle['descripcion']) ?></span>
                        </td>
                        <td><span class="badge badge--neutro"><?= e($detalle['rf']) ?></span></td>
                        <?php foreach ($roles as $rol): ?>
                            <td>
                                <?php if (in_array($rol, $detalle['roles'], true)): ?>
                                    <span class="badge badge--ok">Permitido</span>
                                <?php else: ?>
                                    <span class="badge badge--peligro">Denegado</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="tarjeta" style="margin-top:1.2rem">
    <p class="tarjeta__eyebrow">Asignación</p>
    <h2 class="tarjeta__titulo">Rol actual de cada usuario</h2>
    <p class="nota">
        Cambiar el rol surte efecto en el siguiente inicio de sesión o al recargar la página.
        El sistema no permite quedarse sin administradores activos.
    </p>

    <div class="tabla-envoltura" style="margin-top:1rem">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Rol actual</th>
                    <th scope="col" class="tabla__numero">Asignar rol</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $usuario['nombre']) ?></span>
                            <span class="tabla__secundario"><?= e((string) $usuario['email']) ?></span>
                        </td>
                        <td>
                            <span class="<?= e(clase_badge((string) $usuario['estado'])) ?>">
                                <?= e(nombre_estado_usuario((string) $usuario['estado'])) ?>
                            </span>
                        </td>
                        <td><span class="badge badge--info"><?= e(nombre_rol((string) $usuario['rol'])) ?></span></td>
                        <td class="tabla__numero">
                            <form class="asignar-rol" method="post"
                                  action="<?= e(url('admin/roles/asignar', ['id' => (int) $usuario['id']])) ?>">
                                <?= csrf_field() ?>
                                <select class="campo__control" name="rol"
                                        aria-label="Rol de <?= e((string) $usuario['nombre']) ?>">
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= e($rol) ?>" <?= $usuario['rol'] === $rol ? 'selected' : '' ?>>
                                            <?= e(nombre_rol($rol)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="boton boton--pequeno boton--primario" type="submit">Aplicar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
