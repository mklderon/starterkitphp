<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseValidations
 * Sistema de validación de datos
 * 
 * @version 2.0.0
 */
class PulseValidations
{
    protected $errors = [];
    protected $data = [];

    /**
     * Establece los datos a validar
     * 
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Valida un campo requerido
     * 
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function required(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = $message ?? "El campo {$field} es requerido.";
        }
        
        return $this;
    }

    /**
     * Valida email
     * 
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function email(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser un email válido.";
        }
        
        return $this;
    }

    /**
     * Valida longitud mínima
     * 
     * @param string $field
     * @param int $min
     * @param string|null $message
     * @return self
     */
    public function min(string $field, int $min, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field] = $message ?? "El campo {$field} debe tener al menos {$min} caracteres.";
        }
        
        return $this;
    }

    /**
     * Valida longitud máxima
     * 
     * @param string $field
     * @param int $max
     * @param string|null $message
     * @return self
     */
    public function max(string $field, int $max, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = $message ?? "El campo {$field} no debe exceder {$max} caracteres.";
        }
        
        return $this;
    }

    /**
     * Valida que sea numérico
     * 
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function numeric(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser numérico.";
        }
        
        return $this;
    }

    /**
     * Valida que sea entero
     * 
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function integer(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser un número entero.";
        }
        
        return $this;
    }

    /**
     * Valida que coincida con otro campo
     * 
     * @param string $field
     * @param string $matchField
     * @param string|null $message
     * @return self
     */
    public function matches(string $field, string $matchField, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        $matchValue = $this->data[$matchField] ?? '';
        
        if ($value !== $matchValue) {
            $this->errors[$field] = $message ?? "El campo {$field} debe coincidir con {$matchField}.";
        }
        
        return $this;
    }

    /**
     * Valida con expresión regular
     * 
     * @param string $field
     * @param string $pattern
     * @param string|null $message
     * @return self
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$field] = $message ?? "El campo {$field} tiene un formato inválido.";
        }
        
        return $this;
    }

    /**
     * Valida URL
     * 
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function url(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser una URL válida.";
        }
        
        return $this;
    }

    /**
     * Valida fecha
     * 
     * @param string $field
     * @param string $format Formato esperado (Y-m-d por defecto)
     * @param string|null $message
     * @return self
     */
    public function date(string $field, string $format = 'Y-m-d', ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$field] = $message ?? "El campo {$field} debe ser una fecha válida.";
            }
        }
        
        return $this;
    }

    /**
     * Valida que esté en una lista de valores
     * 
     * @param string $field
     * @param array $values
     * @param string|null $message
     * @return self
     */
    public function in(string $field, array $values, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !in_array($value, $values, true)) {
            $this->errors[$field] = $message ?? "El campo {$field} tiene un valor inválido.";
        }
        
        return $this;
    }

    /**
     * Validación personalizada con callback
     * 
     * @param string $field
     * @param callable $callback
     * @param string|null $message
     * @return self
     */
    public function custom(string $field, callable $callback, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!$callback($value, $this->data)) {
            $this->errors[$field] = $message ?? "El campo {$field} no es válido.";
        }
        
        return $this;
    }

    /**
     * Verifica si hay errores
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verifica si NO hay errores (validación pasó)
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Obtiene todos los errores
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene el error de un campo específico
     * 
     * @param string $field
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Guarda los errores en sesión
     * 
     * @return self
     */
    public function saveToSession(): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['validation_errors'] = $this->errors;
        $_SESSION['old_input'] = $this->data;
        return $this;
    }

    /**
     * Obtiene un error de sesión y lo elimina
     * 
     * @param string $field
     * @return string|null
     */
    public static function getSessionError(string $field): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $error = $_SESSION['validation_errors'][$field] ?? null;
        unset($_SESSION['validation_errors'][$field]);
        
        return $error;
    }

    /**
     * Obtiene el valor anterior de un campo (old input)
     * 
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public static function old(string $field, $default = '')
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        return $_SESSION['old_input'][$field] ?? $default;
    }

    /**
     * Limpia los errores de sesión
     */
    public static function clearSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        unset($_SESSION['validation_errors'], $_SESSION['old_input']);
    }

    /**
     * Limpia los errores actuales
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->errors = [];
        return $this;
    }
}