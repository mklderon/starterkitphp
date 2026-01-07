<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase Dispatcher
 */
class PulseDispatcher
{
    private $router;
    private $url_segments;

    public function __construct()
    {
        $this->router = new PulseRouter();

        $url_path = $this->router->getUrlPath();
        $this->url_segments = explode('/', $url_path);

        $this->url_segments = $this->router->getDevFolderName($this->url_segments);

        $this->loadController($this->url_segments);
    }

    private function loadController($url_segments)
    {
        $controller_name = $this->router->getControllerName($url_segments);
        $method_name = $this->router->getMethodName($url_segments);
        $controller_path = $this->router->getControllerPath($url_segments, $controller_name);
        require_once $controller_path;

        if (class_exists($controller_name)) {
            $controller_instance = new $controller_name();
        } else {
            throw new Exception("Error: La Clase {$controller_name} no está definida en el archivo.");
        }

        $params = $this->router->getMethodParams($url_segments);
        call_user_func_array([$controller_instance, $method_name], $params);
    }
}
