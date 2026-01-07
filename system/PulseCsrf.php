<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseCsrf
 * Protección contra ataques Cross-Site Request Forgery
 * 
 * @version 2.0.0
 */
class PulseCsrf
{
    private const TOKEN_LENGTH = 32; // 32 bytes = 64 caracteres hex
    private const TOKEN_EXPIRATION_TIME = 3600; // 1 hora (más razonable que 15 min)
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_EXPIRATION_NAME = 'csrf_token_expiration';

    /**
     * Genera un nuevo token CSRF y lo almacena en la sesión
     * 
     * @return string El token generado
     */
    public function generateToken(): string
    {
        $this->ensureSessionStarted();

        // Siempre generar un nuevo token o usar el existente si aún es válido
        if (!$this->hasValidToken()) {
            $_SESSION[self::TOKEN_NAME] = $this->generateRandomToken();
            $_SESSION[self::TOKEN_EXPIRATION_NAME] = time() + self::TOKEN_EXPIRATION_TIME;
        }

        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Verifica si el token recibido es válido
     * 
     * @param array $requestData Datos de la petición (POST/GET)
     * @return bool True si el token es válido
     * @throws Exception Si el token falta o es inválido
     */
    public function verifyToken(array $requestData): bool
    {
        $this->ensureSessionStarted();

        // Verificar que el token existe en la petición
        if (!isset($requestData[self::TOKEN_NAME])) {
            throw new Exception("Token CSRF no encontrado en la petición");
        }

        // Verificar que el token existe en la sesión
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            throw new Exception("Token CSRF no encontrado en la sesión");
        }

        // Verificar expiración
        if (!$this->hasValidToken()) {
            $this->regenerateToken();
            throw new Exception("Token CSRF expirado. Por favor, recarga la página e intenta de nuevo.");
        }

        // Comparación segura contra timing attacks
        $isValid = hash_equals(
            $_SESSION[self::TOKEN_NAME],
            $requestData[self::TOKEN_NAME]
        );

        if (!$isValid) {
            throw new Exception("Token CSRF inválido");
        }

        return true;
    }

    /**
     * Genera un campo HTML oculto con el token CSRF
     * 
     * @return string HTML del input hidden
     */
    public function getTokenField(): string
    {
        $token = $this->generateToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_NAME, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Obtiene el token actual (sin generar uno nuevo)
     * 
     * @return string|null El token o null si no existe
     */
    public function getToken(): ?string
    {
        $this->ensureSessionStarted();
        return $_SESSION[self::TOKEN_NAME] ?? null;
    }

    /**
     * Regenera el token (útil después de login/logout)
     * 
     * @return string El nuevo token
     */
    public function regenerateToken(): string
    {
        $this->ensureSessionStarted();
        unset($_SESSION[self::TOKEN_NAME], $_SESSION[self::TOKEN_EXPIRATION_NAME]);
        return $this->generateToken();
    }

    /**
     * Verifica si existe un token válido en la sesión
     * 
     * @return bool
     */
    private function hasValidToken(): bool
    {
        if (!isset($_SESSION[self::TOKEN_NAME]) || !isset($_SESSION[self::TOKEN_EXPIRATION_NAME])) {
            return false;
        }

        return time() < $_SESSION[self::TOKEN_EXPIRATION_NAME];
    }

    /**
     * Genera un token aleatorio criptográficamente seguro
     * 
     * @return string
     */
    private function generateRandomToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Asegura que la sesión esté iniciada
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Limpia el token de la sesión
     */
    public function clearToken(): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[self::TOKEN_NAME], $_SESSION[self::TOKEN_EXPIRATION_NAME]);
    }
}