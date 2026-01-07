<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Pulse App' ?></title>
    <!-- Asegúrate que esta ruta sea correcta -->
    <link rel="stylesheet" href="<?= url_path() ?>public/assets/css/style.css">

    <!-- SCRIPTS PRINCIPALES EN EL HEAD -->
    <script>
        /**
         * Función Global de Notificación (Toast)
         * Definida aquí para que esté disponible antes que cualquier otro script
         */
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            
            // Si el elemento toast no existe, salir
            if (!toast) return;

            // 1. Establecer el mensaje
            toast.textContent = message;

            // 2. Limpiar todas las clases anteriores (para reiniciar estilos)
            toast.className = 'toast';

            // 3. Agregar la clase de color correspondiente (success, error, warning, info)
            if (type === 'success') {
                toast.classList.add('success');
            } else if (type === 'error') {
                toast.classList.add('error');
            } else if (type === 'warning') {
                toast.classList.add('warning');
            } else {
                toast.classList.add('info');
            }

            // 4. Mostrar el toast (agregar clase .show)
            // Usamos requestAnimationFrame para asegurar que el navegador procese el cambio de clases
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            // 5. Ocultar automáticamente después de 4 segundos
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }
    </script>
</head>
<body>
    <!-- Canvas de fondo -->
    <canvas id="canvas-bg"></canvas>

    <!-- El elemento Toast (inicialmente oculto por CSS) -->
    <div id="toast" class="toast">Notificación de sistema</div>

    <!-- Bloque PHP para Mostrar Mensajes Flash -->
    <?php
    // Instanciamos PulseSessions para buscar mensajes
    $session = new PulseSessions();
    $flash = $session->getFlash(); // Esto consume el mensaje de la sesión
    
    if ($flash): ?>
        <script>
            // Esperamos a que el DOM esté listo para ejecutar la función
            document.addEventListener('DOMContentLoaded', function() {
                // Llamamos a la función definida en el <head>
                showToast('<?= addslashes($flash['message']) ?>', '<?= $flash['type'] ?>');
            });
        </script>
    <?php endif; ?>

    <!-- Renderizamos el contenido de la vista (Login o Landing) -->
    <?php if (isset($content)): ?>
        <?= $content ?>
    <?php endif; ?>

    <!-- Scripts de funcionalidad extra (Canvas, etc) -->
    <script>
        // Lógica del Canvas de fondo (Simplificada para que funcione)
        const canvas = document.getElementById('canvas-bg');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let width, height;

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resize);
            resize();

            // Dibujar algo simple
            function draw() {
                ctx.clearRect(0, 0, width, height);
                // Aquí iría tu lógica de partículas si tienes una copiada
                // Por ahora solo dejamos el canvas limpio
            }
            draw();
        }

        /**
         * ROUTER SIMPLE (Para botones de navegación)
         */
        const router = {
            navigate: function(viewName) {
                window.location.href = '<?= url_path() ?>' + viewName;
            }
        };
    </script>
</body>
</html>