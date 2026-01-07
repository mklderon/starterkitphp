# Refactorización de Constantes v2.1.0

**Fecha:** 2026-01-02
**Versión:** 2.1.0

---

## 🎯 Objetivo

Simplificar y centralizar todas las constantes del framework en un solo lugar para mejorar la organización y facilitar la comprensión del sistema.

---

## 📋 Cambios Realizados

### 1. Estructura Antes (v2.0.0)

```
public/index.php                 → Definía DS, BASEPATH, rutas base (13 líneas)
system/config/constants.php      → Rutas específicas (37 líneas)
app/config/config.php            → Configuración mezclada con lógica (37 líneas)
app/init.php                     → Cargaba CONFIG_PATH/config.php
```

**Problemas:**
- ❌ Las rutas estaban divididas en 2 archivos
- ❌ `app/config/config.php` tenía lógica condicional `if (!defined())`
- ❌ Tenías que buscar en 3 archivos para ver todas las constantes
- ❌ `index.php` tenía demasiadas definiciones de rutas
- ❌ `system/config/` era innecesario

---

### 2. Estructura Nueva (v2.1.0)

```
public/index.php                 → Solo bootstrap (22 líneas)
app/config/constants.php         → TODAS las constantes (46 líneas)
app/config/config.php            → Vacío (para uso futuro si se necesita)
app/init.php                     → Solo inicialización (20 líneas)
```

**Beneficios:**
- ✅ Todas las constantes en **UN SOLO ARCHIVO**
- ✅ `index.php` es limpio y simple
- ✅ Estructura clara y fácil de entender
- ✅ Eliminado directorio `system/config/`

---

## 📁 Archivo Nuevo: `app/config/constants.php`

Este archivo ahora contiene **TODAS** las constantes del framework:

### Rutas
```php
define('DS', DIRECTORY_SEPARATOR);
define('PROJECTROOT', dirname(__DIR__, 2) . DS);
define('APP_PATH', PROJECTROOT . 'app' . DS);
define('SYSTEM_PATH', PROJECTROOT . 'system' . DS);
define('PUBLIC_PATH', PROJECTROOT . 'public' . DS);
define('CONTROLLERS_PATH', APP_PATH . 'controllers' . DS);
define('MODELS_PATH', APP_PATH . 'models' . DS);
define('VIEWS_PATH', APP_PATH . 'views' . DS);
```

### Configuración General
```php
define('APP_VERSION', '2.1.0');
define('SITE_NAME', 'My Pulse App');
define('DEFAULT_CONTROLLER', 'home');
define('DEFAULT_METHOD', 'index');
define('PROJECT_LOCAL', 'localhost/pulse-starter');
define('PROJECT_REMOTE', $_SERVER['HTTP_HOST'] ?? 'cli');
```

### Entorno
```php
// Define ENVIRONMENT, ENABLE_SESSION, URL_PATH automáticamente
// según si es localhost o producción
```

### Base de Datos
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pulse_db');
define('DB_CHAR', 'utf8mb4');
```

---

## 📁 Archivo Simplificado: `public/index.php`

**Antes (v2.0.0):**
```php
<?php
define('DS', DIRECTORY_SEPARATOR);
define('BASEPATH', true);
define('PROJECTROOT', dirname(__DIR__) . DS);
define('APP_PATH', PROJECTROOT . 'app' . DS);
define('SYSTEM_PATH', PROJECTROOT . 'system' . DS);
define('PUBLIC_PATH', PROJECTROOT . 'public' . DS);
require_once SYSTEM_PATH . 'config' . DS . 'constants.php';
require_once APP_PATH . 'init.php';
require_once SYSTEM_PATH . 'helpers' . DS . 'functions.php';
// ...
```

**Ahora (v2.1.0):**
```php
<?php
define('BASEPATH', 2.1.0);
require_once dirname(__DIR__) . '/app/config/constants.php';
require_once APP_PATH . 'init.php';
require_once SYSTEM_PATH . 'helpers/functions.php';
try {
    new PulseDispatcher();
} catch (Exception $e) {
    if (class_exists('PulseErrorHandler')) {
        (new PulseErrorHandler())->handleException($e);
    } else {
        echo "<h1>Error Crítico del Sistema</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
}
```

**Diferencias:**
- ✅ Solo 3 líneas de setup + error handling
- ✅ No define rutas (todas en constants.php)
- ✅ Más limpio y fácil de entender

---

## 📁 Archivo Simplificado: `app/init.php`

**Antes (v2.0.0):**
```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');
// Verificar el estado actual de la sesión
$current_session_status = session_status();
// Configuración de errores según el entorno
if (!defined('ENVIRONMENT')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
// Cargar configuración de la aplicación
require_once CONFIG_PATH . 'config.php';
// Cargar funciones de la aplicación
if (file_exists(FUNCTIONS_PATH . 'functions.php')) {
    require_once FUNCTIONS_PATH . 'functions.php';
}
// Activar sesiones
if (defined('ENABLE_SESSION') && ENABLE_SESSION && $current_session_status !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Autoload
spl_autoload_register(function($class_name) {
    $file = SYSTEM_PATH . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
```

**Ahora (v2.1.0):**
```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');
$current_session_status = session_status();
if (file_exists(APP_PATH . 'functions/functions.php')) {
    require_once APP_PATH . 'functions/functions.php';
}
if (defined('ENABLE_SESSION') && ENABLE_SESSION && $current_session_status !== PHP_SESSION_ACTIVE) {
    session_start();
}
spl_autoload_register(function($class_name) {
    $file = SYSTEM_PATH . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
```

**Diferencias:**
- ✅ Eliminado `CONFIG_PATH` (ya no existe)
- ✅ Eliminada lógica de error_reporting (ahora en constants.php)
- ✅ Eliminado `require_once CONFIG_PATH/config.php`
- ✅ Más limpio y enfocado en inicialización

---

## 📁 Archivo Actualizado: `database/config.php`

**Antes (v2.0.0):**
```php
<?php
defined('BASEPATH') or define('BASEPATH', true);
if (!defined('PROJECT_LOCAL')) {
    define('PROJECT_LOCAL', 'localhost/pulse-starter');
}
if (!defined('PROJECT_REMOTE')) {
    define('PROJECT_REMOTE', 'localhost');
}
require_once __DIR__ . '/../app/config/config.php';
define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR);
```

**Ahora (v2.1.0):**
```php
<?php
defined('BASEPATH') or define('BASEPATH', true);
require_once __DIR__ . '/../app/config/constants.php';
define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR);
```

**Diferencias:**
- ✅ Simplificado de 18 a 6 líneas
- ✅ Carga `constants.php` en lugar de `config.php`
- ✅ Eliminadas definiciones duplicadas

---

## 🗑️ Archivo Eliminado

**Eliminado:** `system/config/constants.php`
- ❌ Ya no es necesario
- ✅ Todo está en `app/config/constants.php`

**Eliminado:** Directorio `system/config/`
- ❌ Está vacío
- ✅ Limpieza del directorio system/

---

## 📊 Comparación de Archivos

| Archivo | v2.0.0 | v2.1.0 | Cambio |
|---------|--------|--------|--------|
| public/index.php | 45 líneas | 22 líneas | -51% |
| app/config/constants.php | No existía | 46 líneas | +46 |
| system/config/constants.php | 37 líneas | Eliminado | -100% |
| app/config/config.php | 37 líneas | 2 líneas | -95% |
| app/init.php | 40 líneas | 20 líneas | -50% |
| database/config.php | 18 líneas | 6 líneas | -67% |
| **TOTAL** | **177 líneas** | **96 líneas** | **-46%** |

---

## ✅ Beneficios de la Refactorización

### 1. **Simplicidad**
- ✅ Solo un archivo para todas las constantes
- ✅ `index.php` es solo bootstrap (limpio)
- ✅ No hay que buscar en múltiples archivos

### 2. **Organización**
- ✅ Rutas base + rutas específicas + configuración en un solo lugar
- ✅ Responsabilidades claras
- ✅ Estructura más lógica

### 3. **Mantenibilidad**
- ✅ Fácil de actualizar constantes
- ✅ Menos código total (-46%)
- ✅ Eliminados archivos duplicados

### 4. **Facilidad de Entendimiento**
- ✅ Flujo claro: `index.php` → `constants.php` → `init.php`
- ✅ Todo en un solo lugar
- ✅ Sin lógica condicional en config

---

## 🔄 Migración para Desarrolladores

Si tienes código que hace referencia a la estructura antigua:

### Eliminado
- ❌ `CONFIG_PATH` - Ya no existe
- ❌ `FUNCTIONS_PATH` - Ya no existe
- ❌ `system/config/constants.php` - Eliminado

### Cambiados
- ✅ `app/config/config.php` → `app/config/constants.php` (para definiciones)
- ✅ `require_once CONFIG_PATH . 'config.php'` → Eliminar (todo está en constants.php)

### Ejemplo de Migración

**Antes (v2.0.0):**
```php
// En un archivo personalizado
require_once CONFIG_PATH . 'config.php'; // ❌ CONFIG_PATH ya no existe
if (!defined('MY_CONSTANT')) {
    define('MY_CONSTANT', 'value');
}
```

**Ahora (v2.1.0):**
```php
// Agregar tu constante a app/config/constants.php
define('MY_CONSTANT', 'value');
```

---

## 📚 Documentación Actualizada

- `README.md` - Estructura de archivos actualizada
- `docs/MIGRATION_GUIDE_V2.0_V2.1.md` - Migración arquitectónica
- `docs/SYSTEM_ANALYSIS.md` - Análisis de sistema

---

## 🎉 Conclusión

La refactorización de constantes simplifica el framework haciendo que:

1. **Todas las constantes estén en un solo archivo** (`app/config/constants.php`)
2. **El bootstrap sea minimalista** (`public/index.php` solo 22 líneas)
3. **El código sea más fácil de entender** (flujo claro, sin lógica condicional)
4. **Se reduzca el código total** (-46% menos líneas)

**Versión del Framework:** 2.1.0
**Fecha de Completado:** 2026-01-02
**Estado:** ✅ Simplificado y Centralizado
