/**
 * BiblioTech — Comportamiento del cliente.
 *
 * Mejoras de usabilidad sin dependencias externas:
 *   - Menú de navegación móvil.
 *   - Confirmación antes de operaciones destructivas.
 *   - Ocultado automático de mensajes flash.
 *   - Vista previa del archivo de portada seleccionado.
 *   - Envío de formularios con un solo clic.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        activarMenuMovil();
        confirmarAccionesDestructivas();
        cerrarMensajesAutomaticamente();
        previsualizarPortada();
        evitarDobleEnvio();
        confirmarSalidaConCambios();
    });

    /**
     * Muestra u oculta la navegación principal en pantallas pequeñas.
     */
    function activarMenuMovil() {
        var boton = document.querySelector('[data-menu-alternar]');
        var menu = document.getElementById('menu-principal');

        if (!boton || !menu) {
            return;
        }

        boton.addEventListener('click', function () {
            var abierto = menu.classList.toggle('abierta');
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    }

    /**
     * Pide confirmación antes de ejecutar acciones que eliminan o cancelan
     * información (sección 8: confirmación ante operaciones destructivas).
     */
    function confirmarAccionesDestructivas() {
        document.querySelectorAll('[data-confirmar]').forEach(function (elemento) {
            elemento.addEventListener('click', function (evento) {
                var mensaje = elemento.getAttribute('data-confirmar') || '¿Confirmas esta acción?';

                if (!window.confirm(mensaje)) {
                    evento.preventDefault();
                    evento.stopPropagation();
                }
            });
        });
    }

    /**
     * Cierra los mensajes flash después de unos segundos.
     */
    function cerrarMensajesAutomaticamente() {
        var mensajes = document.querySelectorAll('.mensaje');

        mensajes.forEach(function (mensaje) {
            window.setTimeout(function () {
                mensaje.style.transition = 'opacity 400ms ease';
                mensaje.style.opacity = '0';

                window.setTimeout(function () {
                    if (mensaje.parentNode) {
                        mensaje.parentNode.removeChild(mensaje);
                    }
                }, 420);
            }, 6000);
        });
    }

    /**
     * Muestra el nombre y una vista previa del archivo seleccionado en el
     * campo de carga de portada.
     */
    function previsualizarPortada() {
        var entrada = document.querySelector('[data-vista-previa-archivo]');

        if (!entrada) {
            return;
        }

        var destino = document.querySelector('[data-vista-previa]');
        var etiqueta = document.querySelector('[data-nombre-archivo]');

        entrada.addEventListener('change', function () {
            var archivo = entrada.files && entrada.files[0];

            if (!archivo) {
                return;
            }

            if (etiqueta) {
                etiqueta.textContent = archivo.name + ' (' + Math.round(archivo.size / 1024) + ' KB)';
            }

            if (destino && archivo.type.indexOf('image/') === 0) {
                var lector = new FileReader();

                lector.onload = function (evento) {
                    destino.src = evento.target.result;
                    destino.hidden = false;
                };

                lector.readAsDataURL(archivo);
            }
        });
    }

    /**
     * Deshabilita el botón de envío tras el primer clic para evitar registros
     * duplicados por doble pulsación.
     */
    function evitarDobleEnvio() {
        document.querySelectorAll('form[data-unico-envio]').forEach(function (formulario) {
            formulario.addEventListener('submit', function () {
                var boton = formulario.querySelector('[type="submit"]');

                if (!boton) {
                    return;
                }

                window.setTimeout(function () {
                    boton.disabled = true;
                    boton.dataset.textoOriginal = boton.textContent;
                    boton.textContent = 'Procesando…';
                }, 0);
            });
        });
    }

    /**
     * Avisa si se intenta abandonar un formulario con cambios sin guardar.
     */
    function confirmarSalidaConCambios() {
        var formularios = Array.prototype.slice.call(
            document.querySelectorAll('form[data-aviso-cambios]')
        );

        if (formularios.length === 0) {
            return;
        }

        var enviado = false;

        window.addEventListener('beforeunload', function (evento) {
            if (enviado) {
                return;
            }

            var hayCambios = formularios.some(function (formulario) {
                return formulario.dataset.modificado === '1';
            });

            if (hayCambios) {
                evento.preventDefault();
                evento.returnValue = '';
            }
        });

        formularios.forEach(function (formulario) {
            formulario.addEventListener('input', function () {
                formulario.dataset.modificado = '1';
            });

            formulario.addEventListener('submit', function () {
                enviado = true;
            });
        });
    }
})();
