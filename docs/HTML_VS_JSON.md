# Pulse Framework: HTML vs JSON/AJAX

**Pregunta:** ¿El framework trabaja retornando HTML y/o JSON si se hace una petición por AJAX?

**Respuesta:** ✅ **SÍ, PULSE SOPORTA AMBOS**, pero de forma **EXPLÍCITA** (no automática).

---

## 🎯 Cómo Funciona Pulse

### 📄 Respuestas HTML (Por Defecto)

Por defecto, los controllers retornan **HTML** usando el método `view()`:

```php
class ExampleController extends PulseController
{
    public function index()
    {
        $data = ['title' => 'Mi Página'];
        
        // ✅ RETORNA HTML COMPLETO
        $this->view('home', $data);
    }
}
```

**Resultado:**
```html
<!DOCTYPE html>
<html>
<head><title>Mi Página</title></head>
<body>...</body>
</html>
```

---

### 🔌 Respuestas JSON (Explícito)

Para retornar **JSON**, debes usar explícitamente las funciones JSON:

```php
// Usando helper global
json_response($data, 200, 'Éxito');

// Usando PulseFunctions (desde controller)
$this->jsonResponse('users', $users, 200, 'Usuarios cargados');

// Usando helper de error
json_error('No encontrado', 404);

// Usando PulseFunctions error
$this->jsonError('Error de validación', 400, $errors);
```

**Resultado:**
```json
{
  "success": true,
  "status": 200,
  "data": [...]
}
```

---

## 🔍 Diferencia Clave: Automático vs Explícito

| Framework | Detección AJAX | Uso |
|-----------|----------------|-----|
| **Laravel** | ✅ Automático | Usa `response()->json()` o retorna array |
| **Symfony** | ✅ Automático | Usa `JsonResponse` o retorna string |
| **CodeIgniter** | ✅ Automático | Usa `$this->output->set_content_type()` |
| **Pulse** | ❌ No automático | **Debes elegir explícitamente** |

---

## 💻 Enfoque de Pulse

Pulse usa un enfoque **explícito y flexible**:

### ✅ Opción 1: Controller HTML Único
```php
class PageController extends PulseController
{
    public function index()
    {
        // Siempre retorna HTML
        $this->view('home', $data);
    }
}
```

### ✅ Opción 2: Controller API JSON Único
```php
class ApiController extends PulseController
{
    public function index()
    {
        // Siempre retorna JSON
        json_response($data, 200);
    }
}
```

### ✅ Opción 3: Controller Híbrido (Manual)
```php
class HybridController extends PulseController
{
    public function process()
    {
        $data = $this->getData();

        // Detectar manualmente si es AJAX
        if (is_ajax() || is_json_request()) {
            // Retornar JSON para AJAX
            json_response($data, 200);
        } else {
            // Retornar HTML para navegación normal
            $this->view('result', $data);
        }
    }
}
```

---

## 📚 Funciones Disponibles

### Detectar Tipo de Request

```php
// Verificar si es AJAX (X-Requested-With)
if (is_ajax()) {
    // Petición desde fetch/XMLHttpRequest
}

// Verificar si tiene Content-Type JSON
if (is_json_request()) {
    // Body es JSON
}

// Verificar método HTTP
if (is_method('POST')) {
    // Es POST
}
```

### Respuestas HTML

```php
// Renderizar vista completa
$this->view('home', $data);

// Renderizar vista con más opciones
$this->view('users/index', [
    'title' => 'Usuarios',
    'users' => $users,
    'pagination' => $pagination
]);
```

### Respuestas JSON

```php
// Helper global
json_response($data, 200);  // Success
json_error('Error', 400);   // Error

// PulseFunctions (desde controller)
$this->jsonResponse('users', $users, 200, 'Cargados');
$this->jsonError('No encontrado', 404);
```

---

## 🎨 Ejemplos Prácticos

### Ejemplo 1: Página Web (HTML Únicamente)

```php
// Controller: PageController
class PageController extends PulseController
{
    public function home()
    {
        $articles = $this->model('ArticleModel')->getAll();
        $this->view('home', ['articles' => $articles]);
    }
}

// View: app/views/home.php
<!DOCTYPE html>
<html>
<head><title>Home</title></head>
<body>
    <?php foreach($articles as $article): ?>
        <article>
            <h2><?= e($article['title']) ?></h2>
            <p><?= e($article['content']) ?></p>
        </article>
    <?php endforeach; ?>
</body>
</html>

// Acceso desde navegador:
// http://localhost/pulse-starter/home
// Resultado: HTML completo
```

---

### Ejemplo 2: API REST (JSON Únicamente)

```php
// Controller: ApiController
class ApiController extends PulseController
{
    public function index()
    {
        $users = $this->model('UserModel')->getAll();
        json_response($users, 200, 'Usuarios cargados');
    }

    public function store()
    {
        $data = json_input();

        $user = $this->model('UserModel')->create($data);

        json_response($user, 201, 'Usuario creado');
    }
}

// Acceso desde fetch/axios:
fetch('/api/users')
  .then(res => res.json())
  .then(data => console.log(data));

// Resultado: JSON
// {
//   "success": true,
//   "status": 200,
//   "message": "Usuarios cargados",
//   "data": [...]
// }
```

---

### Ejemplo 3: Controller Híbrido (AJAX + HTML)

```php
// Controller: FormController
class FormController extends PulseController
{
    public function submit()
    {
        // Obtener datos según el tipo
        if (is_json_request()) {
            $data = json_input();  // JSON body
        } else {
            $data = $_POST;  // Form data
        }

        // Validar
        if (empty($data['email'])) {
            if (is_ajax()) {
                // AJAX → Retornar error JSON
                json_error('Email es requerido', 400, [
                    'email' => 'El campo email es obligatorio'
                ]);
            } else {
                // Navegación → Retornar error HTML
                $this->view('form/error', [
                    'error' => 'Email es requerido',
                    'data' => $data
                ]);
            }
        }

        // Procesar
        $result = $this->processForm($data);

        // Responder según tipo de request
        if (is_ajax()) {
            // AJAX → Retornar éxito JSON
            json_response($result, 200, 'Formulario procesado');
        } else {
            // Navegación → Retornar éxito HTML
            $this->view('form/success', $result);
        }
    }
}
```

---

### Ejemplo 4: API SPA (Frontend Framework)

```php
// Controller: SpaController
class SpaController extends PulseController
{
    public function api($endpoint)
    {
        header('Content-Type: application/json');

        switch ($endpoint) {
            case 'users':
                $users = $this->model('UserModel')->getAll();
                json_response($users, 200);
                break;

            case 'products':
                $products = $this->model('ProductModel')->getAll();
                json_response($products, 200);
                break;

            default:
                json_error('Endpoint no encontrado', 404);
        }
    }
}

// JavaScript (SPA con fetch/axios)
fetch('/api/users')
  .then(res => res.json())
  .then(response => {
    if (response.success) {
        renderUsers(response.data);
    }
  });

fetch('/api/products')
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderProducts(response.data);
    }
  });
```

---

## 🔧 Detectar AJAX en el Frontend

### Usando fetch
```javascript
fetch('/api/users', {
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'  // ❗ Para is_ajax()
    },
    method: 'POST',
    body: JSON.stringify({ name: 'Juan' })
})
.then(res => res.json())
.then(data => console.log(data));
```

### Usando axios
```javascript
axios.get('/api/users', {
    headers: {
        'X-Requested-With': 'XMLHttpRequest'  // ❗ Para is_ajax()
    }
})
.then(response => console.log(response.data));

// O con Content-Type JSON
axios.post('/api/users', { name: 'Juan' })
.then(response => console.log(response.data));
```

### Usando jQuery
```javascript
$.ajax({
    url: '/api/users',
    method: 'POST',
    data: { name: 'Juan' },
    success: function(data) {
        console.log(data);
    }
});
// jQuery agrega X-Requested-With automáticamente
```

---

## 📊 Comparativa de Enfoques

| Enfoque | Ventajas | Desventajas | Caso de Uso |
|---------|----------|-------------|--------------|
| **HTML único** | Simplicidad, SEO friendly | No SPA | Sitios web tradicionales |
| **JSON único** | Ideal para SPA/APIs, separación frontend/backend | No navegación sin JS | APIs, aplicaciones SPA |
| **Híbrido** | Soporta ambos, progressive enhancement | Más complejo, código duplicado | Formularios con AJAX fallback |

---

## 💡 Mejores Prácticas

### 1. Separar Controllers
```php
// ✅ Bueno: Separar responsabilidades
class PageController extends PulseController  // HTML
{
    public function index()
    {
        $this->view('home', $data);
    }
}

class ApiController extends PulseController  // JSON
{
    public function index()
    {
        json_response($data, 200);
    }
}

// ❌ No recomendado: Todo en un controller
class Controller extends PulseController
{
    public function index()
    {
        if (is_ajax()) {
            json_response($data);
        } else {
            $this->view('home', $data);
        }
    }
}
```

### 2. Usar rutas específicas
```apache
# /users → HTML
# /api/users → JSON
# /admin/users → HTML
```

### 3. Validar Content-Type
```php
class ApiController extends PulseController
{
    public function store()
    {
        if (!is_json_request()) {
            json_error('Content-Type debe ser application/json', 415);
        }

        $data = json_input();
        // ...
    }
}
```

### 4. Estandarizar respuestas JSON
```php
// ✅ Estandar Pulse (usar estas funciones)
json_response($data, 200, 'Mensaje opcional');
json_error('Error', 400, $errors);

// Respuesta:
{
  "success": true/false,
  "status": 200/400/404/etc,
  "message": "opcional",
  "data": {...}
}
```

---

## 🎯 Resumen

| Pregunta | Respuesta |
|----------|-----------|
| **¿Pulse soporta HTML?** | ✅ Sí, por defecto con `$this->view()` |
| **¿Pulse soporta JSON?** | ✅ Sí, con `json_response()` o `$this->jsonResponse()` |
| **¿Detecta AJAX automáticamente?** | ❌ No, debes detectar manualmente con `is_ajax()` o `is_json_request()` |
| **¿Puedo mezclar ambos?** | ✅ Sí, pero es responsabilidad tuya |
| **¿Recomendación?** | Separar controllers: `PageController` (HTML) y `ApiController` (JSON) |

---

## 📚 Referencias

- Funciones JSON: `system/helpers/functions.php` líneas 90-97, 109-122
- PulseFunctions JSON: `system/PulseFunctions.php` líneas 93-111, 128-143
- Detectors: `is_ajax()`, `is_json_request()`, `is_method()`
- Ejemplos completos: `app/controllers/ApiController.php`

---

**Conclusión:** Pulse es **flexible y explícito**. Tú decides qué tipo de respuesta devolver. No hay "magia" automática, pero tienes todas las herramientas para detectar el tipo de request y responder apropiadamente.
