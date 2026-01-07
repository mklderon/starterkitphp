# Guía de Migración: Cambios en Helpers/Functions

**Versión:** 2.0.0
**Archivos afectados:** `system/helpers/functions.php`, `system/PulseFunctions.php`

---

## ⚠️ Cambios Breaking

### 1. `get_json_input()` renombrada

**Antes:**
```php
$data = get_json_input();
```

**Ahora:**
```php
$data = json_input();
```

**Motivo:** Corrección del typo crítico `php://input` → `php://input` y mejor nombre.

---

### 2. `import_map()` eliminada

**Antes:**
```php
<?= import_map() ?>
```

**Ahora:**
```php
// Esta función ya no existe
// Debes crear tu propio import map si necesitas módulos ES6
```

**Motivo:** `app/src/` ha sido eliminado del starter. Si usas ES6 modules, crea tu propio import map.

---

### 3. Método `method()` - Uso desaconsejado

**Antes:**
```php
$this->method('POST', true);
```

**Ahora (recomendado):**
```php
$this->requestMethod('POST', true);
```

**Nota:** `method()` aún funciona como alias por compatibilidad, pero se considera obsoleto.

---

## 🔄 Cambios Recomendados

### 1. Cache busting en renderScripts

**Antes:**
```php
$this->renderScripts(['app/test.js']);
// Genera: ?v=1704110400  (cada request)
```

**Ahora:**
```php
$this->renderScripts(['app/test.js'], true);  // cache habilitado
// Genera: ?v=2.0.0  (solo cambia la versión)

// O deshabilitar cache:
$this->renderScripts(['app/test.js'], false);
// Sin parámetro de versión
```

---

## ✨ Nuevas Funciones Disponibles

### Helpers Globales

```php
// Debug sin morir
dump($variable);

// Respuesta JSON de error
json_error('Usuario no encontrado', 404);

// Verificar si es JSON request
if (is_json_request()) {
    $data = json_input();
}

// Obtener método HTTP
$method = request_method();      // 'GET', 'POST', etc.
$requestMethod = request_method(true);  // 'GET', 'POST' (uppercase)

// Verificar método específico
if (is_method('POST')) {
    // ...
}

// Sanitización
$safe = sanitize($_POST['input']);

// Escapar para HTML
echo e($user_input);

// Redirección mejorada
redirect('/users');
redirect('https://example.com', 301);

// Array helper
$name = array_get($user, 'name', 'Anónimo');

// URL con parámetros
$url = url_with_params('users', ['page' => 2, 'sort' => 'name']);

// Verificar AJAX
if (is_ajax()) {
    // ...
}

// Obtener IP real
$ip = client_ip();

// Token seguro
$token = random_token(32);

// Generar slug
$slug = slug('Título de Artículo');  // 'titulo-de-articulo'

// Formatear fecha
$date = format_date(time(), 'd/m/Y');

// Formatear bytes
$size = format_bytes(1024 * 1024);  // '1 MB'

// Verificar HTTPS
if (is_https()) {
    // ...
}

// Obtener protocolo
$proto = protocol();  // 'http' o 'https'
```

### PulseFunctions Class

```php
$func = new PulseFunctions();

// JSON de error
$func->jsonError('Validation failed', 400, $errors);

// URL con parámetros
$url = $func->url('users', ['page' => 1]);

// Alertas HTML
$alert = $func->alert('success', 'Guardado correctamente');
echo $alert;

// Paginación
$results = $func->paginate($items, $page, $perPage);
// [
//     'data' => [...],
//     'pagination' => [
//         'current_page' => 1,
//         'per_page' => 15,
//         'total' => 100,
//         'total_pages' => 7,
//         'has_next_page' => true,
//         'has_prev_page' => false
//     ]
// ]
```

---

## 📋 Checklist de Migración

### Para cada Controller:

- [ ] Reemplazar `get_json_input()` → `json_input()`
- [ ] Reemplazar `$this->method()` → `$this->requestMethod()`
- [ ] Verificar uso de `json_response()` (ahora acepta 3 params)
- [ ] Actualizar `$this->renderScripts()` si necesitas control de cache

### Para cada Vista:

- [ ] Verificar si usabas `import_map()` → Eliminar o recrear
- [ ] Considerar usar nuevas funciones: `e()`, `url_path()`, etc.

### Para Funciones Personalizadas:

- [ ] Verificar si usabas funciones ahora disponibles en helpers/functions.php
- [ ] Considerar eliminar duplicados

---

## 🎯 Ejemplos de Migración

### Ejemplo 1: API Endpoint

**Antes:**
```php
class ApiController extends PulseController
{
    public function store()
    {
        $data = get_json_input();
        // ...
        
        $this->jsonResponse('user', $user);
    }
}
```

**Ahora:**
```php
class ApiController extends PulseController
{
    public function store()
    {
        $data = json_input();
        // ...
        
        $this->jsonResponse('user', $user, 200, 'Usuario creado');
    }
}
```

### Ejemplo 2: Form Processing

**Antes:**
```php
class FormController extends PulseController
{
    public function update()
    {
        if ($this->method('POST', true)) {
            $data = $_POST;  // Solo POST
            // ...
        }
    }
}
```

**Ahora:**
```php
class FormController extends PulseController
{
    public function update()
    {
        // Soporta POST, PUT, PATCH
        $data = $this->requestMethod('POST', true);
        
        if ($data === false) {
            json_error('Método no permitido', 405);
        }
        
        // ...
    }
}
```

### Ejemplo 3: View con Helpers

**Antes:**
```php
<div class="card">
    <h1><?= $title ?></h1>
    <p><?= htmlspecialchars($description) ?></p>
</div>
```

**Ahora:**
```php
<div class="card">
    <h1><?= e($title) ?></h1>
    <p><?= e($description) ?></p>
</div>
```

---

## ⚡ Rendimiento

Los cambios incluyen mejoras de rendimiento:

1. **Cache busting inteligente:** Usa `APP_VERSION` en vez de `time()`
2. **Validación JSON temprana:** Evita procesar requests inválidos
3. **Sanitización opcional:** Solo cuando se necesita

---

## 🔧 Solución de Problemas Comunes

### Problema: Function not found `get_json_input`

**Solución:**
```php
// Cambiar a
$data = json_input();
```

### Problema: Import map genera error 404

**Solución:**
```php
// Eliminar import_map() y crear manual si necesitas
<script type="importmap">
{
  "imports": {
    "@": "./"
  }
}
</script>
```

### Problema: Scripts no se actualizan

**Solución:**
```php
// Habilitar cache busting
$this->renderScripts(['app.js'], true);
```

---

## 📚 Recursos Adicionales

- Documentación completa: `docs/REFACTOR_FUNCTIONS.md`
- API de QueryBuilder: `system/QueryBuilder.php`
- Sistema de Sesiones: `system/PulseSessions.php`
- Validaciones: `system/PulseValidations.php`

---

**¿Tienes dudas?** Revisa los ejemplos en `app/controllers/` y `app/views/`.
