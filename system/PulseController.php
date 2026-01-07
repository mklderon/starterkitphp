<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PulseController - Base Controller
 * 
 * Clase base para todos los controllers del framework.
 * Proporciona funcionalidades comunes para cargar modelos, vistas
 * y manejar sesiones.
 * 
 * @version 2.0.0
 * @package PulseFramework
 */
class PulseController
{
    /**
     * Instancia de PulseCsrf
     * @var PulseCsrf
     */
    protected $csrf;

    /**
     * Instancia de PulseFunctions
     * @var PulseFunctions
     */
    protected $function;

    /**
     * Instancia de PulseSessions
     * @var PulseSessions
     */
    protected $session;

    /**
     * Instancia de PulseErrorHandler
     * @var PulseErrorHandler
     */
    protected $errorHandler;

    /**
     * Datos compartidos entre todas las vistas (View Composers)
     * @var array
     */
    protected $viewComposers = [];

    /**
     * Constructor - Inicializa dependencias
     */
    public function __construct()
    {
        $this->csrf = new PulseCsrf();
        $this->function = new PulseFunctions();
        $this->session = new PulseSessions();
        $this->errorHandler = new PulseErrorHandler();
    }

    /**
     * Cargar un modelo
     * 
     * Busca el archivo del modelo en MODELS_PATH y lo instancia.
     * Si la clase no está definida, lanza una excepción.
     * 
     * @param string $modelName Nombre del modelo (sin .php)
     * @return object Instancia del modelo
     * @throws Exception Si el archivo no existe o la clase no está definida
     */
    public function model($modelName)
    {
        // Validar y sanitizar el nombre del modelo
        $sanitizedName = $this->sanitizeFileName($modelName);

        if ($sanitizedName !== $modelName) {
            throw new Exception(
                "Error: El nombre del modelo contiene caracteres inválidos: '{$modelName}'"
            );
        }

        // Verificar que el archivo existe
        $modelPath = MODELS_PATH . $sanitizedName . '.php';

        if (!file_exists($modelPath)) {
            throw new Exception(
                "Error: El Modelo '<b>{$sanitizedName}</b>' no existe en: {$modelPath}"
            );
        }

        // Incluir el archivo del modelo
        require_once $modelPath;

        // Verificar que la clase esté definida
        if (!class_exists($sanitizedName)) {
            throw new Exception(
                "Error: La Clase '<b>{$sanitizedName}</b>' no está definida en el archivo: {$modelPath}"
            );
        }

        // Instanciar el modelo
        return new $sanitizedName();
    }

    /**
     * Cargar una vista
     * 
     * Busca el archivo de vista en VIEWS_PATH y lo incluye.
     * Los datos se pasan a la vista mediante el array.
     * 
     * @param string $viewName Nombre de la vista (sin .php)
     * @param array $data Datos disponibles en la vista (key => value)
     * @throws Exception Si la vista no existe
     */
    public function view($viewName, $data = [])
    {
        // Validar y sanitizar el nombre de la vista
        $sanitizedName = $this->sanitizeFileName($viewName);

        if ($sanitizedName !== $viewName) {
            throw new Exception(
                "Error: El nombre de la vista contiene caracteres inválidos: '{$viewName}'"
            );
        }

        // Verificar que el archivo existe
        $viewPath = VIEWS_PATH . $sanitizedName . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception(
                "Error: La Vista '<b>{$sanitizedName}</b>' no existe en: {$viewPath}"
            );
        }

        // Combinar datos con view composers
        $finalData = array_merge($this->viewComposers, $data);

        // Extraer datos para la vista (DEPRECADO pero mantenido por compatibilidad)
        // ⚠️ SECURITY WARNING: extract() puede sobrescribir variables
        // Se recomienda usar $data['key'] en lugar de $key en las vistas
        extract($finalData);

        // Incluir la vista
        require $viewPath;
    }

    /**
     * Capturar el contenido de una vista
     * 
     * Útil para incluir vistas dentro de otras (layouts, partials, etc.)
     * 
     * @param string $viewName Nombre de la vista
     * @param array $data Datos para la vista
     * @return string Contenido HTML de la vista
     */
    public function capture($viewName, $data = [])
    {
        ob_start();
        $this->view($viewName, $data);
        return ob_get_clean();
    }

    /**
     * Renderizar vista dentro de un layout
     * 
     * @param string $layoutName Nombre del layout (en layouts/)
     * @param string $viewName Nombre de la vista contenido
     * @param array $data Datos para ambas vistas
     * @throws Exception Si el layout no existe
     */
    public function layout($layoutName, $viewName, $data = [])
    {
        $content = $this->capture($viewName, $data);
        $this->view('layouts/' . $layoutName, array_merge($data, ['content' => $content]));
    }

    /**
     * Compartir datos con todas las vistas (View Composers)
     * 
     * Los datos compartidos están disponibles en todas las vistas.
     * Útil para datos como usuario autenticado, configuraciones globales, etc.
     * 
     * @param string $key Clave del dato
     * @param mixed $value Valor del dato
     * @return void
     */
    public function share($key, $value)
    {
        $this->viewComposers[$key] = $value;
    }

    /**
     * Compartir múltiples datos con todas las vistas
     * 
     * @param array $data Array de datos a compartir
     * @return void
     */
    public function shareMany(array $data)
    {
        $this->viewComposers = array_merge($this->viewComposers, $data);
    }

    /**
     * Verificar si el request es AJAX
     * 
     * @return bool
     */
    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verificar si el request tiene Content-Type JSON
     * 
     * @return bool
     */
    protected function isJsonRequest()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($contentType, 'application/json') !== false;
    }

    /**
     * Obtener datos del request según el método
     * 
     * @param bool $sanitize Si es true, sanitiza los datos
     * @return array|false Datos o false si el método no coincide
     */
    protected function getRequestData($sanitize = false)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        switch ($method) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                if ($this->isJsonRequest()) {
                    $data = json_input();
                } else {
                    parse_str(file_get_contents('php://input'), $data);
                }
                break;
            default:
                $data = [];
        }

        return $sanitize ? $this->sanitizeData($data) : $data;
    }

    /**
     * Sanitizar datos recursivamente
     * 
     * @param mixed $data Datos a sanitizar
     * @return mixed Datos sanitizados
     */
    protected function sanitizeData($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }

        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }

    /**
     * Validar y sanitizar nombre de archivo (path traversal prevention)
     * 
     * @param string $fileName Nombre del archivo o vista
     * @return string Nombre sanitizado
     * @throws Exception Si se detecta path traversal
     */
    protected function sanitizeFileName($fileName)
    {
        $originalName = $fileName;

        // Eliminar cualquier referencia a directorios padre
        $fileName = str_replace(['../', '..\\', './', '.\\'], '', $fileName);

        // Permitir solo caracteres alfanuméricos, guiones, puntos y slashes
        $sanitized = preg_replace('/[^a-zA-Z0-9_\/\-\.]/', '', $fileName);

        // Lanzar excepción si la sanitización falla
        if (empty($sanitized)) {
            throw new Exception("Nombre de archivo inválido: {$originalName}");
        }

        // Verificar que no haya paths relativos después de sanitizar
        if (strpos($sanitized, '../') !== false || strpos($sanitized, '..\\') !== false) {
            throw new Exception("Path traversal detectado: {$originalName}");
        }

        return $sanitized;
    }

    /**
     * Verificar si el request es seguro (HTTPS)
     * 
     * @return bool
     */
    protected function isSecure()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Obtener el método HTTP del request
     * 
     * @return string
     */
    protected function getRequestMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Verificar si el método coincide con el esperado
     * 
     * @param string $method Método esperado
     * @return bool
     */
    protected function isMethod($method)
    {
        return strtoupper($method) === $this->getRequestMethod();
    }
}
