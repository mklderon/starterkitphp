<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseErrorHandler
 * Manejo centralizado de errores y excepciones
 * 
 * @version 2.1.0
 */
class PulseErrorHandler
{
    /**
     * Maneja una excepción y muestra el error apropiado
     * 
     * @param Exception $exception
     */
    public function handleException(Exception $exception): void
    {
        // Log del error (en producción)
        $this->logError($exception);

        // Determinar el tipo de error
        $trace = $exception->getTrace();
        $firstTrace = $trace[0]['class'] ?? 'Unknown';

        // Manejar según el tipo
        switch ($firstTrace) {
            case 'PulseRouter':
                $this->handleRoutingException($exception);
                break;
            case 'PulseDatabase':
                $this->handleDatabaseException($exception);
                break;
            default:
                $this->handleGenericException($exception);
                break;
        }
    }

    /**
     * Maneja excepciones de enrutamiento (404, etc.)
     * 
     * @param Exception $exception
     */
    public function handleRoutingException(Exception $exception): void
    {
        http_response_code(404);

        if (ENVIRONMENT === 'development') {
            $this->renderDevelopmentError($exception, 'Error de Enrutamiento');
        } else {
            $this->renderProductionError(
                '404 - Página no encontrada',
                'Lo sentimos, la página que buscas no existe.'
            );
        }
    }

    /**
     * Maneja excepciones de base de datos
     * 
     * @param Exception $exception
     */
    public function handleDatabaseException(Exception $exception): void
    {
        http_response_code(500);

        if (ENVIRONMENT === 'development') {
            $this->renderDevelopmentError($exception, 'Error de Base de Datos');
        } else {
            $this->renderProductionError(
                'Error del Servidor',
                'Ha ocurrido un error en el servidor. Por favor, intenta más tarde.'
            );
        }
    }

    /**
     * Maneja excepciones genéricas
     * 
     * @param Exception $exception
     */
    public function handleGenericException(Exception $exception): void
    {
        http_response_code(500);

        if (ENVIRONMENT === 'development') {
            $this->renderDevelopmentError($exception, 'Error');
        } else {
            $this->renderProductionError(
                'Error',
                'Ha ocurrido un error inesperado. Por favor, intenta más tarde.'
            );
        }
    }

    /**
     * Renderiza un error detallado para desarrollo
     * 
     * @param Exception $exception
     * @param string $title
     */
    private function renderDevelopmentError(Exception $exception, string $title): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #1a1a1a;
                    color: #e0e0e0;
                    padding: 2rem;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: #2d2d2d;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
                }
                .header {
                    background: #c62828;
                    color: white;
                    padding: 1.5rem 2rem;
                    border-bottom: 3px solid #b71c1c;
                }
                .header h1 {
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                .content {
                    padding: 2rem;
                }
                .section {
                    margin-bottom: 2rem;
                    background: #353535;
                    padding: 1.5rem;
                    border-radius: 6px;
                }
                .section h2 {
                    color: #ff6b6b;
                    margin-bottom: 1rem;
                    font-size: 1.1rem;
                }
                .message {
                    background: #424242;
                    padding: 1rem;
                    border-left: 4px solid #ff6b6b;
                    border-radius: 4px;
                    font-family: 'Courier New', monospace;
                    white-space: pre-wrap;
                    word-break: break-word;
                }
                .trace {
                    background: #424242;
                    padding: 1rem;
                    border-radius: 4px;
                    font-family: 'Courier New', monospace;
                    font-size: 0.85rem;
                    overflow-x: auto;
                }
                .trace-item {
                    padding: 0.5rem;
                    border-bottom: 1px solid #555;
                }
                .trace-item:last-child {
                    border-bottom: none;
                }
                .file-info {
                    color: #81c784;
                }
                .line-info {
                    color: #64b5f6;
                }
                .env-badge {
                    display: inline-block;
                    background: #f57c00;
                    color: white;
                    padding: 0.25rem 0.75rem;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    margin-left: 1rem;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>
                        🐛 <?= htmlspecialchars($title) ?>
                        <span class="env-badge">DESARROLLO</span>
                    </h1>
                </div>
                <div class="content">
                    <div class="section">
                        <h2>💬 Mensaje</h2>
                        <div class="message"><?= htmlspecialchars($exception->getMessage()) ?></div>
                    </div>

                    <div class="section">
                        <h2>📍 Ubicación</h2>
                        <div class="message">
                            <span class="file-info"><?= htmlspecialchars($exception->getFile()) ?></span>
                            <span class="line-info"> : línea <?= $exception->getLine() ?></span>
                        </div>
                    </div>

                    <div class="section">
                        <h2>📚 Stack Trace</h2>
                        <div class="trace">
                            <?php foreach ($exception->getTrace() as $index => $trace): ?>
                                <div class="trace-item">
                                    <strong>#<?= $index ?></strong>
                                    <?php if (isset($trace['file'])): ?>
                                        <span class="file-info"><?= htmlspecialchars($trace['file']) ?></span>
                                        <span class="line-info">(<?= $trace['line'] ?? '?' ?>)</span>
                                    <?php endif; ?>
                                    <br>
                                    <?php if (isset($trace['class'])): ?>
                                        <?= htmlspecialchars($trace['class'] . $trace['type'] . $trace['function']) ?>()
                                    <?php elseif (isset($trace['function'])): ?>
                                        <?= htmlspecialchars($trace['function']) ?>()
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Renderiza un error simple para producción
     * 
     * @param string $title
     * @param string $message
     */
    private function renderProductionError(string $title, string $message): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                .error-box {
                    background: white;
                    padding: 3rem;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 500px;
                }
                h1 {
                    color: #333;
                    margin-bottom: 1rem;
                }
                p {
                    color: #666;
                    line-height: 1.6;
                }
                .emoji {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="emoji">😕</div>
                <h1><?= htmlspecialchars($title) ?></h1>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Registra el error en un archivo de log
     * 
     * @param Exception $exception
     */
    private function logError(Exception $exception): void
    {
        if (ENVIRONMENT === 'production') {
            $logFile = PROJECTROOT . 'logs' . DS . 'errors.log';
            $logDir = dirname($logFile);

            // Crear directorio de logs si no existe
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logMessage = sprintf(
                "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
                date('Y-m-d H:i:s'),
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            );

            error_log($logMessage, 3, $logFile);
        }
    }
}