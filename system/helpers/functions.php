<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Helper Functions para Pulse Framework (Sistema)
 * Estas funciones son del core del framework
 * 
 * NOTA: Usamos function_exists() para evitar redeclaraciones
 */

/**
 * Obtiene la URL base del proyecto
 * @param string $append Ruta adicional
 * @return string URL completa
 */
if (!function_exists('url_path')) {
    function url_path($append = '')
    {
        return URL_PATH . ltrim($append, '/');
    }
}

/**
 * Obtiene el nombre del sitio
 * @return string
 */
if (!function_exists('site_name')) {
    function site_name()
    {
        return SITE_NAME;
    }
}

/**
 * Obtiene la versión de la aplicación
 * @return string
 */
if (!function_exists('app_version')) {
    function app_version()
    {
        return APP_VERSION;
    }
}

/**
 * Debug dump and die
 * @param mixed ...$vars Variables a mostrar
 */
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<pre style="background: #1e1e1e; color: #dcdcdc; padding: 20px; border-radius: 4px; overflow-x: auto;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n\n";
        }
        echo '</pre>';
        die();
    }
}

/**
 * Debug dump sin morir
 * @param mixed ...$vars Variables a mostrar
 */
if (!function_exists('dump')) {
    function dump(...$vars)
    {
        echo '<pre style="background: #2d2d2d; color: #dcdcdc; padding: 20px; border-radius: 4px; overflow-x: auto;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n\n";
        }
        echo '</pre>';
    }
}

/**
 * Enviar respuesta JSON estandarizada
 * @param mixed $data Datos a enviar
 * @param int $status Código HTTP (default 200)
 * @param string|null $message Mensaje opcional
 */
if (!function_exists('json_response')) {
    function json_response($data, $status = 200, $message = null)
    {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = [
            'success' => $status < 400,
            'status' => $status,
            'data' => $data,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        echo json_encode($data === null ? $response : $data);
        exit;
    }
}

/**
 * Enviar respuesta JSON de error
 * @param string $message Mensaje de error
 * @param int $status Código HTTP (default 400)
 * @param mixed $errors Errores adicionales
 */
if (!function_exists('json_error')) {
    function json_error($message, $status = 400, $errors = null)
    {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }
}

/**
 * Obtener datos JSON del request body
 * @param bool $asObject Si es true, retorna object en vez de array
 * @return array|object|null
 */
if (!function_exists('json_input')) {
    function json_input($asObject = false)
    {
        $jsonInput = file_get_contents('php://input');

        if (empty($jsonInput)) {
            return $asObject ? (object)[] : [];
        }

        $data = json_decode($jsonInput, !$asObject);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $asObject ? (object)[] : [];
        }

        return $data;
    }
}

/**
 * Verificar si el request es un JSON request
 * @return bool
 */
if (!function_exists('is_json_request')) {
    function is_json_request()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($contentType, 'application/json') !== false;
    }
}

/**
 * Obtener el método HTTP del request
 * @param bool $uppercase Si es true, retorna en mayúsculas
 * @return string
 */
if (!function_exists('request_method')) {
    function request_method($uppercase = true)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return $uppercase ? strtoupper($method) : $method;
    }
}

/**
 * Verificar si el método HTTP coincide con el esperado
 * @param string $method Método esperado
 * @return bool
 */
if (!function_exists('is_method')) {
    function is_method($method)
    {
        return strtoupper($method) === request_method();
    }
}

/**
 * Sanitizar un valor o array de valores
 * @param mixed $data Datos a sanitizar
 * @return mixed Datos sanitizados
 */
if (!function_exists('sanitize')) {
    function sanitize($data)
    {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Escapar string para HTML
 * @param string $string String a escapar
 * @return string
 */
if (!function_exists('e')) {
    function e($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Redireccionar a una URL
 * @param string $url URL destino
 * @param int $status Código HTTP (default 302)
 */
if (!function_exists('redirect')) {
    function redirect($url, $status = 302)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = URL_PATH . ltrim($url, '/');
        }

        http_response_code($status);
        header("Location: $url");
        exit;
    }
}

/**
 * Obtener valor de array con fallback
 * @param array $array Array donde buscar
 * @param string $key Clave a buscar
 * @param mixed $default Valor por defecto
 * @return mixed
 */
if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }
}

/**
 * Generar URL con parámetros
 * @param string $path Ruta base
 * @param array $params Parámetros
 * @return string
 */
if (!function_exists('url_with_params')) {
    function url_with_params($path, array $params = [])
    {
        $url = url_path($path);
        if (empty($params)) {
            return $url;
        }
        return $url . '?' . http_build_query($params);
    }
}

/**
 * Verificar si es un request AJAX
 * @return bool
 */
if (!function_exists('is_ajax')) {
    function is_ajax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Obtener IP del cliente
 * @return string
 */
if (!function_exists('client_ip')) {
    function client_ip()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}

/**
 * Generar token aleatorio seguro
 * @param int $length Longitud del token
 * @return string
 */
if (!function_exists('random_token')) {
    function random_token($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}

/**
 * Generar slug a partir de string
 * @param string $string String a convertir
 * @param string $separator Separador (default: -)
 * @return string
 */
if (!function_exists('slug')) {
    function slug($string, $separator = '-')
    {
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/[^a-z0-9]+/', $separator, $string);
        $string = trim($string, $separator);
        return $string;
    }
}

/**
 * Formatear fecha/hora
 * @param string|int $date Fecha o timestamp
 * @param string $format Formato de salida (default: Y-m-d H:i:s)
 * @return string
 */
if (!function_exists('format_date')) {
    function format_date($date, $format = 'Y-m-d H:i:s')
    {
        if (is_numeric($date)) {
            return date($format, $date);
        }
        return date($format, strtotime($date));
    }
}

/**
 * Formatear tamaño de archivo
 * @param int $bytes Tamaño en bytes
 * @param int $precision Precisión decimal
 * @return string
 */
if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Verificar si es HTTPS
 * @return bool
 */
if (!function_exists('is_https')) {
    function is_https()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}

/**
 * Obtener protocolo actual (http/https)
 * @return string
 */
if (!function_exists('protocol')) {
    function protocol()
    {
        return is_https() ? 'https' : 'http';
    }
}
