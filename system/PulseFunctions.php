<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseFunctions
 * Funciones utilitarias del framework disponibles en controllers
 * 
 * @version 2.0.0
 */
class PulseFunctions
{
    /**
     * Redireccionar a una URL
     * @param string $target_page Página o URL destino
     * @param int $status Código HTTP (default 302)
     */
    public function redirectTo($target_page, $status = 302)
    {
        $url = $target_page;

        // Si no es URL completa, agregar URL_PATH
        if (!filter_var($target_page, FILTER_VALIDATE_URL)) {
            $url = URL_PATH . ltrim($target_page, '/');
        }

        http_response_code($status);
        header("Location: $url");
        exit();
    }

    /**
     * Renderizar hojas de estilos CSS
     * @param array $styles Array de rutas CSS
     */
    public function renderStyles($styles)
    {
        if (empty($styles) || !is_array($styles)) {
            return;
        }

        foreach ($styles as $style) {
            $styleUrl = $this->buildAssetUrl($style);
            echo sprintf('<link rel="stylesheet" href="%s">%s', $styleUrl, "\n");
        }
    }

    /**
     * Renderizar scripts JavaScript
     * @param array $scripts Array de rutas JS
     * @param bool $useCache Si false, deshabilita cache busting (default: true)
     */
    public function renderScripts($scripts, $useCache = true)
    {
        if (empty($scripts) || !is_array($scripts)) {
            return;
        }

        $version = $useCache ? '?v=' . APP_VERSION : '';

        foreach ($scripts as $script) {
            $scriptUrl = $this->buildAssetUrl($script);
            $moduleAttr = $this->isModuleScript($script) ? ' type="module"' : '';

            echo sprintf('<script src="%s%s"%s></script>%s', $scriptUrl, $version, $moduleAttr, "\n");
        }
    }

    /**
     * Construye URL correcta para assets
     * @param string $assetPath Ruta del asset
     * @return string URL completa
     */
    private function buildAssetUrl($assetPath)
    {
        // Si ya es URL completa
        if (preg_match('/^https?:\/\//', $assetPath)) {
            return $assetPath;
        }

        // Si empieza con '/', es ruta absoluta desde el dominio
        if (strpos($assetPath, '/') === 0) {
            return protocol() . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $assetPath;
        }

        // Por defecto, usar URL_PATH
        return URL_PATH . ltrim($assetPath, '/');
    }

    /**
     * Detecta si un script debe cargarse como módulo ES6
     * @param string $scriptPath Ruta del script
     * @return bool
     */
    private function isModuleScript($scriptPath)
    {
        // Archivos .mjs son módulos por definición
        if (substr($scriptPath, -4) === '.mjs') {
            return true;
        }

        // Scripts que contienen 'app/src' son módulos (legacy support)
        if (strpos($scriptPath, 'app/src/') !== false) {
            return true;
        }

        // Archivos main.js o index.js suelen ser entry points
        if (preg_match('/(main|index)\.js$/i', basename($scriptPath))) {
            return true;
        }

        return false;
    }

    /**
     * Enviar respuesta JSON de éxito
     * @param string $responseName Nombre del campo de datos
     * @param mixed $data Datos a enviar
     * @param int $status Código HTTP (default 200)
     * @param string $message Mensaje opcional
     */
    public function jsonResponse($responseName, $data, $status = 200, $message = null)
    {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = [
            'success' => $status < 400,
            'status' => $status,
            $responseName => $data,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        $jsonEncoded = json_encode($response);
        
        if ($jsonEncoded === false) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al codificar la respuesta JSON'
            ]);
        } else {
            echo $jsonEncoded;
        }
        
        die();
    }

    /**
     * Enviar respuesta JSON de error
     * @param string $message Mensaje de error
     * @param int $status Código HTTP (default 400)
     * @param array $errors Errores adicionales
     */
    public function jsonError($message, $status = 400, $errors = [])
    {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors
        ]);
        
        die();
    }

    /**
     * Encriptar password usando bcrypt
     * @param string $password Password en texto plano
     * @return string Hash del password
     */
    public function encryptPass($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verificar password contra su hash
     * @param string $password Password en texto plano
     * @param string $hash Hash del password
     * @return bool
     */
    public function verifyPass($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Obtener datos del request HTTP
     * @param string $method Método esperado (GET, POST, etc.)
     * @param bool $sanitize Si es true, sanitiza los datos
     * @return array|false Datos o false si el método no coincide
     */
    public function requestMethod($method, $sanitize = false)
    {
        $expectedMethod = strtoupper($method);
        $actualMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($actualMethod !== $expectedMethod) {
            return false;
        }

        // Obtener datos según el método
        $data = [];
        switch ($actualMethod) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                if (is_json_request()) {
                    $data = json_input();
                }
                // Para PUT/DELETE con form-data, parse php://input
                parse_str(file_get_contents('php://input'), $data);
                break;
        }

        // Sanitizar si se solicita
        if ($sanitize && !empty($data)) {
            $data = $this->sanitizeData($data);
        }

        return $data;
    }

    /**
     * Alias de requestMethod para compatibilidad
     * @deprecated Usar requestMethod() en su lugar
     */
    public function method($method_data, $sanitize = false)
    {
        return $this->requestMethod($method_data, $sanitize);
    }

    /**
     * Obtener datos JSON del request body
     * @param bool $sanitize Si es true, sanitiza los datos
     * @return array|false Datos o false si no es JSON
     */
    public function getJsonData($sanitize = false)
    {
        if (!is_json_request()) {
            return false;
        }

        $data = json_input();

        if ($data === null) {
            return false;
        }

        if ($sanitize) {
            $data = $this->sanitizeData($data);
        }

        return $data;
    }

    /**
     * Sanitizar datos recursivamente
     * @param mixed $data Datos a sanitizar
     * @return mixed Datos sanitizados
     */
    private function sanitizeData($data)
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
     * Generar URL con parámetros
     * @param string $path Ruta base
     * @param array $params Parámetros
     * @return string
     */
    public function url($path = '', array $params = [])
    {
        $url = URL_PATH . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Renderizar un template de notificación
     * @param string $type Tipo (success, error, warning, info)
     * @param string $message Mensaje
     * @param bool $dismissible Si es cerrable
     * @return string HTML de la alerta
     */
    public function alert($type, $message, $dismissible = true)
    {
        $types = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700'
        ];

        $class = $types[$type] ?? $types['info'];
        $dismissBtn = $dismissible ? 
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' : '';

        return sprintf(
            '<div class="border-l-4 p-4 mb-4 %s" role="alert">%s<strong>%s</strong></div>',
            $class,
            $dismissBtn,
            e($message)
        );
    }

    /**
     * Paginar array de resultados
     * @param array $items Items a paginar
     * @param int $page Página actual
     * @param int $perPage Items por página
     * @return array ['data' => [], 'pagination' => []]
     */
    public function paginate($items, $page = 1, $perPage = 15)
    {
        $total = count($items);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($items, $offset, $perPage),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_prev_page' => $page > 1
            ]
        ];
    }
}
