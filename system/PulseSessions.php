<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseSessions
 * Manejo simplificado y seguro de sesiones
 * 
 * @version 2.0.0
 */
class PulseSessions
{
    /**
     * Constructor - asegura que la sesión esté iniciada
     */
    public function __construct()
    {
        $this->ensureSessionStarted();
    }

    /**
     * Establece datos en la sesión
     * 
     * @param string $name Nombre de la clave
     * @param mixed $data Datos a guardar
     * @return bool
     */
    public function set(string $name, $data): bool
    {
        $_SESSION[$name] = $data;
        return true;
    }

    /**
     * Alias de set() para compatibilidad
     */
    public function setUserData(string $name, $data): bool
    {
        return $this->set($name, $data);
    }

    /**
     * Obtiene datos de la sesión
     * 
     * @param string $name Nombre de la clave
     * @param string|null $key Subclave opcional
     * @return mixed|null
     */
    public function get(string $name, ?string $key = null)
    {
        if (!isset($_SESSION[$name])) {
            return null;
        }

        if ($key !== null) {
            return $_SESSION[$name][$key] ?? null;
        }

        return $_SESSION[$name];
    }

    /**
     * Alias de get() para compatibilidad
     */
    public function getUserData(string $name, ?string $key = null)
    {
        return $this->get($name, $key);
    }

    /**
     * Verifica si existe una clave en la sesión
     * 
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Elimina una clave de la sesión
     * 
     * @param string $name
     * @return bool
     */
    public function unset(string $name): bool
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
            return true;
        }
        return false;
    }

    /**
     * Alias de unset() para compatibilidad
     */
    public function unsetUserData(string $name): bool
    {
        return $this->unset($name);
    }

    /**
     * Destruye completamente la sesión
     * 
     * @return bool
     */
    public function destroy(): bool
    {
        $_SESSION = [];

        // Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        return session_destroy();
    }

    /**
     * Alias de destroy() para compatibilidad
     */
    public function sessionDestroy(): bool
    {
        return $this->destroy();
    }

    /**
     * Regenera el ID de sesión (útil después de login)
     * 
     * @param bool $deleteOldSession
     * @return bool
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        return session_regenerate_id($deleteOldSession);
    }

    // ========================================================================
    // SISTEMA DE MENSAJES FLASH
    // ========================================================================

    /**
     * Establece un mensaje flash
     * 
     * @param string $type Tipo: success, error, warning, info
     * @param string $message Mensaje
     * @return bool
     */
    public function setFlash(string $type, string $message): bool
    {
        $_SESSION['_flash'] = [
            'type' => $type,
            'message' => $message
        ];
        return true;
    }

    /**
     * Alias de setFlash() para compatibilidad
     */
    public function setFlashMessage(string $type, string $message): bool
    {
        return $this->setFlash($type, $message);
    }

    /**
     * Obtiene el mensaje flash completo y lo elimina
     * 
     * @return array|null ['type' => '...', 'message' => '...']
     */
    public function getFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) {
            return null;
        }

        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /**
     * Obtiene solo el tipo del flash
     * 
     * @param string $expectedType Tipo esperado
     * @return string|false
     */
    public function getFlashType(string $expectedType = '')
    {
        if (!isset($_SESSION['_flash']['type'])) {
            return false;
        }

        $type = $_SESSION['_flash']['type'];

        if ($expectedType && $type !== $expectedType) {
            return false;
        }

        return $type;
    }

    /**
     * Obtiene solo el mensaje del flash y lo elimina
     * 
     * @return string|false
     */
    public function getFlashContent()
    {
        $flash = $this->getFlash();
        return $flash ? $flash['message'] : false;
    }

    /**
     * Verifica si hay un mensaje flash
     * 
     * @return bool
     */
    public function hasFlash(): bool
    {
        return isset($_SESSION['_flash']);
    }

    // ========================================================================
    // MÉTODOS DE CONVENIENCIA PARA FLASH
    // ========================================================================

    public function flashSuccess(string $message): bool
    {
        return $this->setFlash('success', $message);
    }

    public function flashError(string $message): bool
    {
        return $this->setFlash('error', $message);
    }

    public function flashWarning(string $message): bool
    {
        return $this->setFlash('warning', $message);
    }

    public function flashInfo(string $message): bool
    {
        return $this->setFlash('info', $message);
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Asegura que la sesión esté iniciada
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            
            session_start();
        }
    }

    /**
     * Obtiene todos los datos de la sesión
     * 
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Limpia todos los datos de la sesión sin destruirla
     * 
     * @return bool
     */
    public function clear(): bool
    {
        $_SESSION = [];
        return true;
    }
}