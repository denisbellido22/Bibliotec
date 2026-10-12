<?php
/**
 * BiblioTech — Ficha del usuario.
 *
 * Variables: $usuario, $motivos.
 */

declare(strict_types=1);
?>
<div class="encabezado">
    <div>
        <h1 class="encabezado__titulo"><?= e((string) $usuario['nombre']) ?></h1>
        <p class="encabezado__subtitulo"><?= e((string) $usuario['email']) ?></p>
    </div>
    <div class="encabezado__acciones">
        <a class="boton boton--secundario"
           href="<?= e(url('admin/usuarios/editar', ['id' => (int) $usuario['id']])) ?>">Editar</a>
        <a class="boton boton--plano" href="<?= e(url('admin/usuarios')) ?>">Volver</a>
    </div>
</div>

<section class="rejilla rejilla--dos">
    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Cuenta</p>
        <h2 class="tarjeta__titulo">Datos del usuario</h2>

        <ul class="lista-datos">
            <li><span>Identificador</span><strong>#<?= e((string) $usuario['id']) ?></strong></li>
            <li><span>Rol</span><strong><?= e(nombre_rol((string) $usuario['rol'])) ?></strong></li>
            <li>
                <span>Estado</span>
                <strong>
                    <span class="<?= e(clase_badge((string) $usuario['estado'])) ?>">
                        <?= e(nombre_estado_usuario((string) $usuario['estado'])) ?>
                    </span>
                </strong>
            </li>
            <li><span>Registrado</span><strong><?= e(fecha_hora($usuario['created_at'] ?? null)) ?></strong></li>
            <li><span>Última actualización</span><strong><?= e(fecha_hora($usuario['updated_at'] ?? null)) ?></strong></li>
        </ul>
    </div>

    <div class="tarjeta">
        <p class="tarjeta__eyebrow">Integridad</p>
        <h2 class="tarjeta__titulo">Historial asociado</h2>

        <?php if ($motivos === []): ?>
            <p class="nota">Este usuario no tiene préstamos ni reservas registradas, por lo que su cuenta puede eliminarse.</p>
        <?php else: ?>
            <p class="nota">La cuenta tiene movimientos registrados y no puede eliminarse sin romper el historial:</p>
            <ul class="lista-datos">
                <?php foreach ($motivos as $motivo): ?>
                    <li><span><?= e($motivo) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p class="nota">Si ya no debe acceder, usa la opción <strong>Bloquear</strong> del listado.</p>
        <?php endif; ?>
    </div>
</section>
