<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Clase PulseRouter
 */
class PulseRouter
{
    private $controller_name_path;

    public function getUrlPath()
    {
        $url_path = $_GET['url'] ?? '/';
        if (is_string($url_path)) {
            $url_path = rtrim($url_path, '/');
            $url_path = str_replace(['-', ' '], ['_', '_'], $url_path);
            $url_path = filter_var($url_path, FILTER_SANITIZE_URL);
            return $url_path;
        }

        return '/';
    }

    public function getDevFolderName($url_segments)
    {
        $directory_path = CONTROLLERS_PATH . $url_segments[0];

        if (is_dir($directory_path)) {
            $this->controller_name_path = $directory_path . DS;
            unset($url_segments[0]);
            $url_segments = array_values($url_segments);
        } else {
            $this->controller_name_path = CONTROLLERS_PATH . DS;
        }

        return $url_segments;
    }

    public function getControllerName($url_segments)
    {
        $controller_name = ucfirst($url_segments[0] ?? DEFAULT_CONTROLLER) . 'Controller';
        return $controller_name;
    }

    public function getMethodName($url_segments)
    {
        return $url_segments[1] ?? DEFAULT_METHOD;
    }

    public function getControllerPath($url_segments, $controller_name)
    {
        $controller_path = $this->controller_name_path . $controller_name . '.php';

        if (!file_exists($controller_path)) {
            throw new Exception("Error: El Controlador '{$controller_name}' no existe.");
        }

        return $controller_path;
    }

    public function getMethodParams($url_segments)
    {
        return array_slice($url_segments, 2);
    }
}
