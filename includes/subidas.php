<?php
/**
 * BiblioTech — Carga segura de portadas.
 *
 * La imagen se selecciona desde el explorador de archivos (input type="file").
 * El servidor comprueba el tipo real con finfo, limita el tamaño, renombra el
 * archivo y lo guarda fuera del alcance de la ejecución de PHP.
 */

declare(strict_types=1);

/**
 * Guarda la portada enviada por el formulario.
 *
 * @param array<string, mixed>|null $archivo Entrada de $_FILES o null.
 * @param string|null               $error   Mensaje de error legible.
 * @return string|null Nombre del archivo guardado, o null si no se subió nada.
 */
function guardar_portada(?array $archivo, ?string &$error = null): ?string
{
    $error = null;

    if ($archivo === null || !isset($archivo['error'])) {
        return null;
    }

    // Sin archivo seleccionado: no es un error.
    if ((int) $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) $archivo['error'] !== UPLOAD_ERR_OK) {
        $error = match ((int) $archivo['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL                        => 'La imagen se subió de forma incompleta.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar la imagen.',
            default                                   => 'No se pudo procesar la imagen seleccionada.',
        };

        return null;
    }

    $temporal = (string) ($archivo['tmp_name'] ?? '');

    if ($temporal === '' || !is_uploaded_file($temporal)) {
        $error = 'El archivo recibido no es válido.';

        return null;
    }

    $maxBytes = (int) config('uploads.max_bytes', 2097152);
    $tamano   = (int) ($archivo['size'] ?? 0);

    if ($tamano <= 0 || $tamano > $maxBytes) {
        $error = 'La imagen no debe superar los ' . round($maxBytes / 1048576, 1) . ' MB.';

        return null;
    }

    // Tipo real del contenido, no la extensión declarada por el navegador.
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = (string) $finfo->file($temporal);
    $permitidos = (array) config('uploads.mimes', []);

    if (!array_key_exists($mime, $permitidos)) {
        $error = 'Formato no permitido. Usa JPG, PNG, WEBP o GIF.';

        return null;
    }

    $extension = (string) $permitidos[$mime];
    $nombre    = 'portada_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

    $destino = ruta_portadas();

    if (!is_dir($destino) && !mkdir($destino, 0775, true) && !is_dir($destino)) {
        $error = 'No se pudo preparar la carpeta de portadas.';

        return null;
    }

    if (!move_uploaded_file($temporal, $destino . DIRECTORY_SEPARATOR . $nombre)) {
        $error = 'No se pudo guardar la imagen en el servidor.';

        return null;
    }

    return $nombre;
}

/** Ruta absoluta de la carpeta de portadas. */
function ruta_portadas(): string
{
    return (string) config('uploads.ruta', BASE_PATH . '/uploads/portadas');
}

/**
 * Elimina una portada local reemplazada.
 *
 * Sólo borra archivos que estén dentro de la carpeta de portadas: las
 * direcciones externas (por ejemplo Open Library) no se tocan.
 */
function eliminar_portada_local(?string $archivo): void
{
    if ($archivo === null || trim($archivo) === '') {
        return;
    }

    if (preg_match('#^https?://#i', $archivo) === 1) {
        return;
    }

    $ruta = ruta_portadas() . DIRECTORY_SEPARATOR . basename($archivo);

    if (is_file($ruta)) {
        @unlink($ruta);
    }
}
