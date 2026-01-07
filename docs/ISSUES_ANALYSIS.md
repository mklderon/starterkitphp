# Evaluación de Issues Encontrados en Pulse Framework v2.1.0

**Fecha:** 2026-01-02
**Analista:** Usuario del framework
**Versión Evaluada:** 2.1.0

---

## 📋 Resumen de Evaluación

| Categoría | Issues Encontrados | Issues Confirmados | Issues Falsos Positivos | Prioridad |
|-----------|-------------------|-------------------|------------------------|-----------|
| Errores Críticos | 2 | 1 | 1 | Alta |
| Riesgos de Seguridad | 3 | 3 | 0 | Alta |
| Inconsistencias y Configuración | 2 | 2 | 0 | Media |
| Redundancias y Malas Prácticas | 4 | 4 | 0 | Media-Alta |
| **TOTAL** | **11** | **10** | **1** | - |

---

## 1. Errores Críticos

### ❌ Issue A: Sobrescritura de datos JSON en métodos PUT/DELETE
**Archivo:** `system/PulseController.php` (método `getRequestData`, no `PulseFunctions.php`)

**Estado:** ❌ **FALSO POSITIVO** - Ya corregido en v2.1.0

**Análisis:**
El usuario menciona que el código en `PulseFunctions.php` tiene un error en el método `requestMethod`, pero en v2.1.0:
- El método correcto es `getRequestData()` en `PulseController.php`
- El código **YA TIENE** la estructura `if/else` correcta:

```php
case 'PUT':
case 'DELETE':
case 'PATCH':
    if ($this->isJsonRequest()) {
        $data = json_input();      // ✅ Lee JSON
    } else {
        parse_str(file_get_contents('php://input'), $data); // ✅ Lee string
    }
    break;
```

**Conclusión:** Este issue ya está corregido en la versión actual. Posiblemente el usuario estaba analizando código de v2.0.0.

---

### ✅ Issue B: Reinicio automático de QueryBuilder impide reutilización
**Archivo:** `system/QueryBuilder.php`
**Método:** `execute` (líneas 399-408)

**Estado:** ✅ **CONFIRMADO** - Issue real

**Código actual:**
```php
private function execute(string $sql)
{
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->bindings);

    // Reset para próxima query
    $this->reset(); // ❌ Problema: rompe reutilización

    return $stmt;
}
```

**Problema:**
```php
$users = new QueryBuilder();
$users->where('status', 'active');
$users->select('id', 'name')->get();      // ✅ Funciona
$users->select('id', 'email')->get();     // ❌ Error: query está vacío (fue reseteado)
```

**Impacto:**
- ❌ Rompe el principio de reutilización
- ❌ Impide encadenar operaciones sobre la misma instancia
- ❌ Comportamiento antinatural en un QueryBuilder con "Fluent Interface"

**Solución Recomendada:**
Opción 1: Eliminar `reset()` y documentar que se debe crear nueva instancia
Opción 2: Agregar método `reset()` público que el usuario llame explícitamente

**Prioridad:** Media-Alta

---

## 2. Riesgos de Seguridad

### ✅ Issue A: Listado de directorios habilitado en `app`
**Archivo:** `app/.htaccess`
**Línea:** 26

**Estado:** ✅ **CONFIRMADO** - Riesgo de seguridad real

**Código actual:**
```apache
# Permitir acceso a directorios (para importar módulos)
Options +Indexes
```

**Problema:**
- ❌ Permite ver listado de archivos en `app/` si no hay `index.php`
- ❌ Expone estructura del código (controladores, modelos, vistas)
- ❌ Permite explorar directorios de la aplicación

**Impacto de seguridad:**
- 🔴 Alto: Revela arquitectura de la aplicación
- 🔴 Medio: Permite enumerar archivos y estructura

**Solución Recomendada:**
```apache
# Bloquear listado de directorios
Options -Indexes
```

**Prioridad:** Alta (Riesgo de seguridad)

---

### ✅ Issue B: CORS demasiado permisivo
**Archivo:** `public/.htaccess`
**Líneas:** 20-24

**Estado:** ✅ **CONFIRMADO** - Riesgo de seguridad real

**Código actual:**
```apache
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"
```

**Problema:**
- ❌ Permite peticiones desde CUALQUIER origen (`*`)
- ❌ Aceptable en desarrollo, PELIGROSO en producción
- ❌ Permite ataques CSRF de otros dominios

**Impacto de seguridad:**
- 🟡 Medio: Permite cualquier origen en producción

**Solución Recomendada:**
```apache
# En producción, usar dominios específicos
<IfModule mod_headers.c>
    # Solo para desarrollo
    <If "%{HTTP_HOST} =~ /^(localhost|127\.0\.0\.1)/">
        Header set Access-Control-Allow-Origin "*"
    </If>

    # Para producción (descomentar y configurar)
    # Header set Access-Control-Allow-Origin "https://midominio.com"
</IfModule>
```

**Prioridad:** Media-Alta (Configuración de producción)

---

### ✅ Issue C: Sanitización de nombres de archivo (Path Traversal)
**Archivo:** `system/PulseController.php`
**Método:** `sanitizeFileName` (líneas 280-290)

**Estado:** ✅ **CONFIRMADO** - Riesgo de seguridad real

**Código actual:**
```php
protected function sanitizeFileName($fileName)
{
    // Eliminar cualquier referencia a directorios padre
    $fileName = str_replace(['../', '..\\', './', '.\\'], '', $fileName);

    // Permitir solo caracteres alfanuméricos, guiones y slashes
    $sanitized = preg_replace('/[^a-zA-Z0-9_\/\-]/', '', $fileName);

    // Si el resultado está vacío, devolver el original para no romper funcionalidad existente
    return $sanitized ?: $fileName; // ❌ RIESGO: Devuelve original si sanitización falla
}
```

**Problema:**
```php
// Ejemplo de ataque:
$malicious = "../../../etc/passwd";
// str_replace elimina "../" → "etc/passwd"
// preg_replace elimina caracteres → "etcpasswd"
// Devuelve "etcpasswd" (inofensivo)

// PERO, si el input tiene caracteres prohibidos:
$malicious2 = "@@##$$"; // Solo caracteres especiales
// preg_replace elimina todo → ""
// Devuelve "@@##$$" (el original) ❌ RIESGO
```

**Problema real:**
- ❌ Si la sanitización devuelve string vacío, devuelve el INPUT ORIGINAL
- ❌ Esto puede permitir bypass de sanitización
- ❌ El regex elimina el `.` por completo, rompiendo archivos con puntos

**Impacto de seguridad:**
- 🟡 Medio-Bajo: Dependiendo del contexto de uso

**Solución Recomendada:**
```php
protected function sanitizeFileName($fileName)
{
    $fileName = str_replace(['../', '..\\', './', '.\\'], '', $fileName);
    $sanitized = preg_replace('/[^a-zA-Z0-9_\/\-\.]/', '', $fileName);

    // Lanzar excepción si la sanitización falla
    if (empty($sanitized)) {
        throw new Exception("Nombre de archivo inválido: {$fileName}");
    }

    // Verificar que no haya paths relativos después de sanitizar
    if (strpos($sanitized, '../') !== false || strpos($sanitized, '..\\') !== false) {
        throw new Exception("Path traversal detectado: {$fileName}");
    }

    return $sanitized;
}
```

**Prioridad:** Media (Mejora de seguridad)

---

## 3. Inconsistencias y Configuración

### ✅ Issue A: Rutas Hardcoded (Fragilidad)
**Archivos:**
1. `public/.htaccess` - Línea 7
2. `app/config/constants.php` - Línea 18

**Estado:** ✅ **CONFIRMADO** - Problema real

**Código actual:**

**1. public/.htaccess:**
```apache
RewriteBase /pulse-starter/public/
```

**2. app/config/constants.php:**
```php
define('PROJECT_LOCAL', 'localhost/pulse-starter');
```

**Problema:**
- ❌ El framework asume que la carpeta se llama `pulse-starter`
- ❌ Si el usuario renombra la carpeta, la web deja de funcionar
- ❌ No es flexible para despliegues en diferentes rutas

**Impacto:**
- ❌ Bajo en desarrollo (fácil de cambiar manualmente)
- ❌ Alto en producción (fácil olvidar cambiarlo)

**Solución Recomendada:**

**Para .htaccess (opción 1 - eliminar RewriteBase):**
```apache
# No usar RewriteBase si está en la raíz del dominio
# O detectar dinámicamente
```

**Para constants.php (opción 1 - detección automática):**
```php
// Detectar automáticamente la ruta local
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('/public', '', $scriptDir);
define('PROJECT_LOCAL', $_SERVER['HTTP_HOST'] . $scriptDir);
```

**Prioridad:** Media (Mejora de usabilidad)

---

### ✅ Issue B: Detección de errores frágil en `PulseErrorHandler`
**Archivo:** `system/PulseErrorHandler.php`
**Método:** `handleException` (líneas 17-38)

**Estado:** ✅ **CONFIRMADO** - Problema real

**Código actual:**
```php
public function handleException(Exception $exception): void
{
    $this->logError($exception);

    // Determinar el tipo de error
    $trace = $exception->getTrace();
    $firstTrace = $trace[0]['class'] ?? 'Unknown'; // ❌ Problemático

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
```

**Problema:**
```php
// Si el error ocurre en un Controller:
throw new Exception("Error en HomeController");
// Trace[0]['class'] = 'HomeController'
// → Se maneja como error genérico (correcto)

// Pero si PulseRouter lanza una excepción:
throw new Exception("Ruta no encontrada");
// Trace[0]['class'] = 'PulseRouter'
// → Se maneja como error de ruteo (correcto)

// PROBLEMA: Si el Controller llama un método de PulseRouter:
class HomeController extends PulseController {
    public function index() {
        $this->router->someMethod(); // Lanza excepción
    }
}
// Trace[0]['class'] = 'PulseRouter'
// → Se maneja como error de ruteo (incorrecto, es error de Controller)
```

**Impacto:**
- 🟡 Bajo: En la mayoría de casos funciona correctamente
- 🟡 Medio: Puede clasificar incorrectamente errores en edge cases

**Solución Recomendada:**
Usar códigos de excepción personalizados en lugar de analizar el trace:

```php
// Crear excepciones específicas:
class RoutingException extends Exception {}
class DatabaseException extends Exception {}

// Y luego:
try {
    // código que puede lanzar errores
} catch (RoutingException $e) {
    $this->handleRoutingException($e);
} catch (DatabaseException $e) {
    $this->handleDatabaseException($e);
} catch (Exception $e) {
    $this->handleGenericException($e);
}
```

**Prioridad:** Media (Mejora de arquitectura)

---

## 4. Redundancias y Malas Prácticas

### ✅ Issue A: Reflexión (Reflection) para acceder a la base de datos
**Archivo:** `system/QueryBuilder.php`
**Método:** `fromDatabase` (líneas 63-69)

**Estado:** ✅ **CONFIRMADO** - Code Smell real

**Código actual:**
```php
private function fromDatabase(PulseDatabase $db): void
{
    $reflection = new ReflectionClass($db);
    $property = $reflection->getProperty('dbh'); // ❌ Accediendo propiedad privada
    $property->setAccessible(true);
    $this->pdo = $property->getValue($db);
}
```

**Problema:**
- ❌ Rompe encapsulación (propiedad `dbh` es `private`)
- ❌ Usa Reflection para "hackear" el acceso
- ❌ Indica diseño acoplado incorrectamente
- ❌ Code Smell: mal olor de código

**Por qué es malo:**
- Violación de principios de encapsulación
- Difícil de mantener si cambia PulseDatabase
- No es la forma correcta de obtener una dependencia

**Impacto:**
- 🟡 Medio: Funciona, pero es una mala práctica

**Solución Recomendada:**
```php
// Opción 1: Hacer dbh protected en PulseDatabase
class PulseDatabase {
    protected $dbh; // Cambiar de private a protected
}

// Opción 2: Agregar método getter en PulseDatabase
class PulseDatabase {
    public function getPdo(): PDO {
        return $this->dbh;
    }
}

// Y usarlo en QueryBuilder:
private function fromDatabase(PulseDatabase $db): void {
    $this->pdo = $db->getPdo();
}
```

**Prioridad:** Media-Alta (Mejora de arquitectura)

---

### ✅ Issue B: `extract()` en Vistas
**Archivo:** `system/PulseController.php`
**Método:** `view` (líneas 104-134)

**Estado:** ✅ **CONFIRMADO** - Ya documentado, pero sigue siendo un problema

**Código actual:**
```php
// Extraer datos para la vista (DEPRECADO pero mantenido por compatibilidad)
// ⚠️ SECURITY WARNING: extract() puede sobrescribir variables
extract($finalData);
```

**Problema:**
- ❌ Contamina el ámbito de variables en las vistas
- ❌ Puede sobrescribir variables internas del sistema
- ❌ Difícil de rastrear de dónde vienen las variables

**Ejemplo de problema:**
```php
// En el controller:
$this->view('home', [
    'title' => 'Home Page',
    'query' => 'malicious' // Sobrescribe si la vista usa $query
]);

// En la vista:
<h1><?= $title ?></h1>
<?php $query = $this->db->query('...'); // ❌ Error: $query ya existe ?>
```

**Impacto:**
- 🟡 Medio-Bajo: Documentado como deprecado, pero sigue siendo un riesgo

**Solución Recomendada:**
Ya está documentado. Para v3.0.0, eliminar `extract()` y obligar el uso de `$data['key']`.

**Prioridad:** Baja (Ya documentado como deprecado)

---

### ✅ Issue C: Dependencias de archivos manuales dentro de Clases
**Archivos:**
- `system/PulseDispatcher.php` (líneas 4-5)
- `system/PulseModel.php` (línea 3)

**Estado:** ✅ **CONFIRMADO** - Problema real

**Código actual:**

**PulseDispatcher.php:**
```php
require_once 'PulseErrorHandler.php';
require_once 'PulseRouter.php';
```

**PulseModel.php:**
```php
require_once 'QueryBuilder.php';
```

**Problema:**
- ❌ El framework tiene `spl_autoload_register` en `init.php`
- ❌ Usar `require_once` dentro de las clases rompe el autoloader
- ❌ Mantenimiento de rutas manual es propenso a errores
- ❌ No sigue buenas prácticas de PHP moderno

**Por qué es malo:**
- Duplica la lógica de carga de clases
- Rutas relativas (`'QueryBuilder.php'`) son frágiles
- No aprovecha el autoloader que ya existe

**Impacto:**
- 🟡 Medio: Funciona, pero no sigue buenas prácticas

**Solución Recomendada:**
Eliminar todos los `require_once` dentro de las clases. El autoloader se encargará de cargar las dependencias automáticamente.

**Prioridad:** Media (Mejora de arquitectura)

---

### ✅ Issue D: Redundancia en Configuración
**Archivo:** `app/config/constants.php`
**Líneas:** 22-30

**Estado:** ✅ **CONFIRMADO** - Redundancia real

**Código actual:**
```php
if (in_array($httpHost, ['localhost', '127.0.0.1', 'cli']) || strpos($httpHost, 'localhost:') === 0) {
    define('ENVIRONMENT', 'development');
    define('ENABLE_SESSION', TRUE);
    define('URL_PATH', 'http://' . PROJECT_LOCAL . '/');
} else {
    define('ENVIRONMENT', 'production');
    define('ENABLE_SESSION', TRUE); // ❌ Redundante
    define('URL_PATH', 'https://' . PROJECT_REMOTE . '/');
}
```

**Problema:**
- ❌ `ENABLE_SESSION` se define exactamente igual en ambos branches
- ❌ Debería salir del `if/else`

**Solución Recomendada:**
```php
// Definir ENABLE_SESSION antes del if/else
define('ENABLE_SESSION', TRUE);

if (in_array($httpHost, ['localhost', '127.0.0.1', 'cli']) || strpos($httpHost, 'localhost:') === 0) {
    define('ENVIRONMENT', 'development');
    define('URL_PATH', 'http://' . PROJECT_LOCAL . '/');
} else {
    define('ENVIRONMENT', 'production');
    define('URL_PATH', 'https://' . PROJECT_REMOTE . '/');
}
```

**Impacto:**
- 🟢 Bajo: Solo es redundancia, no causa errores funcionales

**Prioridad:** Baja (Mejora de limpieza de código)

---

## 5. Detalles Menores

### 1. Duplicidad PulseFunctions vs Helpers Globales
**Estado:** ✅ **CONFIRMADO**

Hay mucha duplicidad:
- `json_response` (helper global) vs `jsonResponse` (PulseFunctions)
- Otras funciones duplicadas

**Impacto:** 🟡 Medio: Confusión sobre cuál usar

**Recomendación:** Unificar en una sola forma de acceso

---

### 2. Tipado inconsistente
**Estado:** ✅ **CONFIRMADO**

Mezcla de estilos:
- `public function uploadImage(string $fieldName...)` (tipado estricto)
- `public function url($path = '')` (sin tipado)

**Recomendación:** Habilitar `declare(strict_types=1);` en todos los archivos

---

### 3. Validación de `ENABLE_SESSION`
**Estado:** ✅ **CONFIRMADO**

El flujo parece correcto, pero podría mejorarse con validación más estricta.

---

## 📊 Resumen de Acciones Recomendadas

### Prioridad ALTA (Urgente)
1. ✅ **Eliminar** `Options +Indexes` de `app/.htaccess` (Riesgo de seguridad)
2. ✅ **Refactorizar** `PulseDatabase` para exponer PDO sin Reflection (Code smell)

### Prioridad MEDIA-ALTA
3. ✅ **Eliminar** `$this->reset()` de `QueryBuilder::execute()` (rompe reutilización)
4. ✅ **Configurar** CORS por entorno en `public/.htaccess` (Seguridad en producción)
5. ✅ **Mejorar** `sanitizeFileName()` para no devolver input original (Mejora de seguridad)
6. ✅ **Eliminar** todos los `require_once` dentro de clases (Arquitectura)

### Prioridad MEDIA
7. ✅ **Eliminar** `RewriteBase` hardcoded o documentar claramente (Flexibilidad)
8. ✅ **Usar** excepciones específicas en lugar de analizar trace (Arquitectura)

### Prioridad BAJA
9. ✅ **Eliminar** redundancia de `ENABLE_SESSION` en constants.php (Limpieza)
10. ✅ **Unificar** PulseFunctions y helpers globales (Consistencia)
11. ✅ **Habilitar** `declare(strict_types=1);` (Tipado)

---

## 🎉 Conclusión

**Análisis del usuario:**
- ✅ **Excelente:** Encontró 11 issues relevantes
- ✅ **Preciso:** 10 de 11 son confirmados
- ❌ **1 Falso Positivo:** Issue A (JSON sobrescritura) ya corregido en v2.1.0

**Calidad del análisis:** ⭐⭐⭐⭐⭐ (5/5)

**Estado del framework:**
- Framework funcional pero con room for improvement
- 3 riesgos de seguridad que deben corregirse
- 4 code smells que afectan la arquitectura
- Varios detalles menores que pueden mejorarse

**Próximos pasos recomendados:**
1. Corregir issues de prioridad ALTA
2. Documentar cambios en guías de seguridad
3. Planificar refactorizaciones para v2.2.0 o v3.0.0

**Versión del Framework:** 2.1.0
**Fecha de Evaluación:** 2026-01-02
**Analista:** Usuario del framework
