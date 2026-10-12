<?php
/**
 * BiblioTech — Listado de usuarios.
 *
 * Variables: $usuarios, $filtros, $resumen.
 */

declare(strict_types=1);
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo">Usuarios</h1>
        <p class="encabezado__subtitulo">
            <?= e((string) count($usuarios)) ?> resultado(s) ·
            <?= e((string) $resumen['total']) ?> usuarios registrados ·
            <?= e((string) $resumen['activos']) ?> activos ·
            <?= e((string) $resumen['bloqueados']) ?> bloqueados
        </p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--primario" href="<?= e(url('admin/usuarios/crear')) ?>">Registrar usuario</a>
    </div>
</div>

<?php /* RF10: búsqueda por nombre o correo · RF11: filtros por rol y estado */ ?>
<section class="filtros">
    <form class="filtros__formulario" method="get" action="<?= e(base_url()) ?>/index.php" role="search">
        <input type="hidden" name="ruta" value="admin/usuarios">

        <div class="filtros__campo">
            <label class="campo__etiqueta" for="q">Buscar por nombre o correo</label>
            <input class="campo__control" type="search" id="q" name="q"
                   value="<?= e((string) $filtros['texto']) ?>" placeholder="Ej.: Lucía, carlos@bibliotech.test">
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="rol">Rol</label>
            <select class="campo__control" id="rol" name="rol">
                <option value="">Todos los roles</option>
                <?php foreach (Usuario::ROLES as $rol): ?>
                    <option value="<?= e($rol) ?>" <?= $filtros['rol'] === $rol ? 'selected' : '' ?>>
                        <?= e(nombre_rol($rol)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__campo filtros__campo--corto">
            <label class="campo__etiqueta" for="estado">Estado</label>
            <select class="campo__control" id="estado" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (Usuario::ESTADOS as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                        <?= e(nombre_estado_usuario($estado)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--primario" type="submit">Filtrar</button>
            <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<?php if ($usuarios === []): ?>
    <div class="vacio">
        <h3>Sin resultados</h3>
        <p>No hay usuarios que coincidan con la búsqueda o los filtros aplicados.</p>
        <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Ver todos los usuarios</a>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Préstamos</th>
                    <th scope="col" class="tabla__numero">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <span class="tabla__principal"><?= e((string) $usuario['nombre']) ?></span>
                            <span class="tabla__secundario"><?= e((string) $usuario['email']) ?></span>
                        </td>
                        <td><span class="badge badge--info"><?= e(nombre_rol((string) $usuario['rol'])) ?></span></td>
                        <td>
                            <span class="<?= e(clase_badge((string) $usuario['estado'])) ?>">
                                <?= e(nombre_estado_usuario((string) $usuario['estado'])) ?>
                            </span>
                        </td>
                        <td><?= e((string) $usuario['total_prestamos']) ?></td>
                        <td class="tabla__numero">
                            <div class="tabla__acciones">
                                <a class="boton boton--pequeno boton--plano"
                                   href="<?= e(url('admin/usuarios/ver', ['id' => (int) $usuario['id']])) ?>">Ver</a>
                                <a class="boton boton--pequeno boton--secundario"
                                   href="<?= e(url('admin/usuarios/editar', ['id' => (int) $usuario['id']])) ?>">Editar</a>

                                <form method="post" action="<?= e(url('admin/usuarios/estado', ['id' => (int) $usuario['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--pequeno boton--plano" type="submit"
                                            data-confirmar="¿Cambiar el estado de <?= e((string) $usuario['nombre']) ?>?">
                                        <?= $usuario['estado'] === 'activo' ? 'Bloquear' : 'Activar' ?>
                                    </button>
                                </form>

                                <form method="post" action="<?= e(url('admin/usuarios/eliminar', ['id' => (int) $usuario['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--pequeno boton--peligro" type="submit"
                                            data-confirmar="¿Eliminar la cuenta de <?= e((string) $usuario['nombre']) ?>? Esta acción no se puede deshacer.">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
