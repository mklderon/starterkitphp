# Guía Completa: Clases del Sistema Pulse

**Versión:** 2.0.0
**Fecha:** 2026-01-01

Este documento explica cómo usar cada una de las clases del sistema Pulse Framework.

---

## 📋 ÍNDICE

1. [PulseCsrf](#1-pulsecrsf) - Protección CSRF
2. [PulseErrorHandler](#2-pulseerrorhandler) - Manejo de errores
3. [PulseFunctions](#3-pulsefunctions) - Funciones utilitarias
4. [PulseModel](#4-pulsemodel) - Modelos con QueryBuilder
5. [PulseSessions](#5-pulsesessions) - Sesiones
6. [PulseValidations](#6-pulsevalidations) - Validaciones
7. [PulseFile](#7-pulsefile) - Upload de archivos

---

## 1. 🔒 PulseCsrf - Protección CSRF

### ¿Qué hace?
Protege contra ataques Cross-Site Request Forgery (CSRF).

### Métodos Disponibles

| Método | Descripción | Uso |
|--------|-------------|-----|
| `generateToken()` | Genera/retorna token CSRF | Formularios |
| `verifyToken($data)` | Verifica token del request | POST/PUT/DELETE |
| `getTokenField()` | Retorna HTML `<input hidden>` | Formularios |
| `getToken()` | Retorna token actual (sin generar nuevo) | AJAX requests |
| `regenerateToken()` | Regenera token (después de login/logout) | Seguridad |
| `clearToken()` | Limpia token de sesión | Logout |

### Ejemplos de Uso

#### 1.1 Formulario HTML con CSRF

```php
// Controller
class FormController extends PulseController
{
    public function showForm()
    {
        $this->view('form/create', [
            'title' => 'Crear Registro'
        ]);
    }

    public function submit()
    {
        try {
            // ✅ Verificar token CSRF
            PulseCsrf::verifyToken($_POST);

            // Token válido - Procesar datos
            $name = $_POST['name'];
            // ... procesar ...

            // Redirigir
            redirect('/dashboard');

        } catch (PulseErrorHandler $e) {
            // ❌ Token inválido o expirado
            echo "Error: " . $e->getMessage();
        }
    }
}
```

```html
<!-- Vista: app/views/form/create.php -->
<form method="POST" action="/form/submit">
    <!-- ✅ Campo CSRF requerido -->
    <?= PulseCsrf::getTokenField() ?>
    
    <input type="text" name="name" required>
    <button type="submit">Guardar</button>
</form>
```

#### 1.2 API con CSRF en Header

```php
class ApiController extends PulseController
{
    public function create()
    {
        try {
            // Token puede venir en body o header
            $token = $_POST[PulseCsrf::TOKEN_NAME] 
                  ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                  ?? null;

            if (!$token) {
                json_error('Token CSRF requerido', 400);
            }

            PulseCsrf::verifyToken([PulseCsrf::TOKEN_NAME => $token]);

            // Token válido - Procesar
            $data = json_input();
            json_response($data, 201, 'Creado exitosamente');

        } catch (PulseErrorHandler $e) {
            json_error($e->getMessage(), 403);
        }
    }
}
```

```javascript
// Frontend (fetch)
fetch('/api/create', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token  // Token desde GET /csrf/token
    },
    body: JSON.stringify({ name: 'Juan' })
})
.then(res => res.json())
```

#### 1.3 Regenerar Token después de Login

```php
class AuthController extends PulseController
{
    public function login()
    {
        // ... validar credenciales ...

        // Login exitoso
        $this->session->set('user_id', $userId);
        $this->session->set('is_logged_in', true);

        // ✅ Regenerar token CSRF (seguridad)
        PulseCsrf::regenerateToken();

        redirect('/dashboard');
    }

    public function logout()
    {
        // ✅ Limpiar token CSRF
        PulseCsrf::clearToken();

        // Destruir sesión
        $this->session->destroy();

        redirect('/login');
    }
}
```

---

## 2. 🐛 PulseErrorHandler - Manejo de Errores

### ¿Qué hace?
Maneja excepciones y muestra errores bonitos (dev) o simples (prod).

### Métodos Disponibles

| Método | Descripción |
|--------|-------------|
| `handleException($e)` | Maneja excepción principal |
| `handleRoutingException($e)` | Errores 404, routing |
| `handleDatabaseException($e)` | Errores de base de datos |
| `handleGenericException($e)` | Errores genéricos |

### Uso Automático

El framework maneja errores automáticamente. Solo necesitas lanzar excepciones:

```php
// Lanzar error 404
throw new PulseErrorHandler("Página no encontrada");

// El framework mostrará:
// Development: Error detallado con stack trace
// Production: Página bonita de 404
```

### Uso Manual

```php
try {
    $user = $this->model('UserModel')->getById($id);
    
    if (!$user) {
        throw new Exception("Usuario no encontrado");
    }
    
} catch (Exception $e) {
    // El framework detectará el tipo y mostrará el error apropiado
    PulseErrorHandler::handleException($e);
}
```

### Personalización

Para personalizar páginas de error, crea:
- `app/views/errors/404.php`
- `app/views/errors/500.php`
- Etc.

---

## 3. 🛠️ PulseFunctions - Funciones Utilitarias

Accesible desde controllers como `$this->function`.

### Métodos Principales

```php
// ✅ Redirección
$this->function->redirectTo('/dashboard');
$this->function->redirectTo('/users', 301);

// ✅ Renderizar assets
$this->function->renderStyles(['css/style.css']);
$this->function->renderScripts(['js/app.js'], true);  // true = con cache

// ✅ Respuestas JSON
$this->function->jsonResponse('users', $users, 200, 'Cargados');
$this->function->jsonError('Error', 400, $errors);

// ✅ Contraseñas
$hash = $this->function->encryptPass('password123');
$isValid = $this->function->verifyPass('password123', $hash);

// ✅ Obtener datos del request
$data = $this->function->requestMethod('POST', true);  // POST sanitizado
$jsonData = $this->function->getJsonData(true);  // JSON sanitizado
```

### Ejemplo Completo

```php
class DataController extends PulseController
{
    public function processData()
    {
        try {
            // Obtener datos según el método
            if ($this->isMethod('POST')) {
                $data = $this->function->requestMethod('POST', true);
            } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                $data = $this->function->getJsonData(true);
            } else {
                $this->function->jsonError('Método no permitido', 405);
            }

            // Procesar datos
            $result = $this->doSomething($data);

            // Retornar JSON de éxito
            $this->function->jsonResponse('result', $result, 200, 'Datos procesados');

        } catch (Exception $e) {
            // Retornar JSON de error
            $this->function->jsonError($e->getMessage(), 500);
        }
    }
}
```

---

## 4. 💾 PulseModel - Modelos con QueryBuilder

### ¿Qué hace?
Proporciona acceso a la base de datos mediante QueryBuilder.

### Método Principal

```php
// Cargar modelo
$userModel = $this->model('UserModel');
$docModel = $this->model('DoctorModel');
```

### QueryBuilder (disponible en el modelo)

```php
// ✅ SELECT básico
$users = $model->qb('users')->get();

// ✅ SELECT con WHERE
$activeUsers = $model->qb('users')
    ->where('is_active', 1)
    ->where('role', 'admin')
    ->get();

// ✅ SELECT con ORDER BY
$users = $model->qb('users')
    ->orderBy('created_at', 'DESC')
    ->get();

// ✅ SELECT con LIMIT
$recentUsers = $model->qb('users')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// ✅ SELECT con JOIN
$results = $model->qb('users u')
    ->select('u.*', 'p.name as profile_name')
    ->join('profiles p', 'u.id', '=', 'p.user_id')
    ->where('u.is_active', 1)
    ->get();

// ✅ INSERT
$userId = $model->qb('users')->insert([
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'password' => password_hash('123456', PASSWORD_DEFAULT)
]);

// ✅ UPDATE
$model->qb('users')
    ->where('id', $userId)
    ->update([
        'name' => 'Juan Actualizado',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

// ✅ DELETE
$model->qb('users')->where('id', $userId)->delete();

// ✅ COUNT
$count = $model->qb('users')->count();

// ✅ EXISTS
$exists = $model->qb('users')->where('email', 'test@example.com')->exists();

// ✅ FIRST (un solo registro)
$user = $model->qb('users')->where('id', 1)->first();

// ✅ FIND (por ID)
$user = $model->qb('users')->find(1);
```

### Transacciones

```php
$model->qb('transaction', function($qb) use ($data1, $data2) {
    $userId = $qb->table('users')->insert($data1);
    $qb->table('profiles')->insert(['user_id' => $userId] + $data2);
});
```

---

## 5. 🍪 PulseSessions - Sesiones

### ¿Qué hace?
Maneja sesiones de PHP de forma segura y sencilla.

### Métodos Principales

```php
// ✅ SET - Guardar datos
$this->session->set('user_id', 123);
$this->session->set('user_data', ['name' => 'Juan']);
$this->session->set('is_logged_in', true);

// ✅ GET - Obtener datos
$userId = $this->session->get('user_id');
$userData = $this->session->get('user_data');
$userId = $this->session->get('user_id', 'default_value');

// ✅ HAS - Verificar si existe dato
if ($this->session->has('user_id')) {
    // Usuario tiene sesión
}

// ✅ UNSET - Eliminar dato
$this->session->unset('user_id');

// ✅ DESTROY - Destruir sesión completa
$this->session->destroy();

// ✅ REGENERATE - Regenerar ID de sesión
$this->session->regenerate(true);  // true = borrar sesión anterior

// ✅ FLASH - Mensajes temporales
$this->session->flashSuccess('Operación completada');
$this->session->flashError('Hubo un error');
$this->session->flashWarning('Revise los datos');
$this->session->flashInfo('Información importante');

// ✅ FLASH - Leer y eliminar
$flash = $this->session->getFlash();

// ✅ ALL - Obtener todos los datos
$allData = $this->session->all();

// ✅ CLEAR - Limpiar sin destruir
$this->session->clear();
```

### Ejemplo Completo: Login/Logout

```php
class AuthController extends PulseController
{
    public function login()
    {
        $this->view('auth/login');
    }

    public function authenticate()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Verificar CSRF
        PulseCsrf::verifyToken($_POST);

        // Verificar credenciales
        $userModel = $this->model('UserModel');
        $user = $userModel->getByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            // Error - Guardar mensaje flash
            $this->session->flashError('Credenciales inválidas');
            redirect('/auth/login');
        }

        // Login exitoso
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_name', $user['name']);
        $this->session->set('is_logged_in', true);
        $this->session->set('last_login', date('Y-m-d H:i:s'));

        // Regenerar ID de sesión (seguridad)
        $this->session->regenerate(true);

        // Mensaje flash de éxito
        $this->session->flashSuccess('¡Bienvenido ' . $user['name'] . '!');

        redirect('/dashboard');
    }

    public function logout()
    {
        // Limpiar token CSRF
        PulseCsrf::clearToken();

        // Destruir sesión
        $this->session->destroy();

        // Mensaje flash
        $this->session->flashInfo('Has cerrado sesión correctamente');

        redirect('/auth/login');
    }

    public function dashboard()
    {
        // Verificar si está autenticado
        if (!$this->session->has('user_id')) {
            $this->session->flashWarning('Debes iniciar sesión para acceder');
            redirect('/auth/login');
        }

        // Obtener datos del usuario
        $userId = $this->session->get('user_id');
        $userName = $this->session->get('user_name');
        $lastLogin = $this->session->get('last_login');

        $this->view('auth/dashboard', [
            'user_id' => $userId,
            'user_name' => $userName,
            'last_login' => $lastLogin
        ]);
    }
}
```

---

## 6. ✅ PulseValidations - Validaciones

### ¿Qué hace?
Valida datos de formularios con una API fluida.

### Métodos Disponibles

```php
$validation = new PulseValidations();

$validation->setData($data)
    ->required('name')
    ->email('email')
    ->min('password', 8)
    ->max('name', 50)
    ->numeric('age')
    ->integer('phone')
    ->matches('password', 'password_confirmation')
    ->regex('phone', '/^[0-9]{10}$/')
    ->url('website')
    ->date('birth_date')
    ->in('role', ['admin', 'user', 'guest'])
    ->custom('email', function($value, $allData) {
        return !in_array($value, ['used@example.com', 'taken@example.com']);
    });

// Verificar si hay errores
if ($validation->hasErrors()) {
    $errors = $validation->getErrors();
}

// Verificar si pasó validación
if ($validation->passes()) {
    // Datos válidos
}

// Guardar en sesión para usar en siguiente request
$validation->saveToSession();

// Obtener error específico
$nameError = $validation->getError('name');
```

### Ejemplo Completo

```php
class RegistrationController extends PulseController
{
    public function form()
    {
        $this->view('registration/form');
    }

    public function register()
    {
        // Verificar CSRF
        PulseCsrf::verifyToken($_POST);

        $data = $_POST;

        // Validar datos
        $validation = new PulseValidations();
        $validation->setData($data)
            ->required('name')
            ->min('name', 2)
            ->max('name', 50)
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', 8)
            ->matches('password', 'password_confirmation')
            ->required('role')
            ->in('role', ['admin', 'user', 'guest']);

        if ($validation->hasErrors()) {
            // Guardar en sesión para mostrar en el formulario
            $validation->saveToSession();

            $this->session->flashError('Por favor, corrige los errores');
            redirect('/register');
        }

        // Validación exitosa - Guardar en BD
        $userModel = $this->model('UserModel');
        $userId = $userModel->create($data);

        // Guardar ID de usuario en sesión
        $this->session->set('user_id', $userId);

        $this->session->flashSuccess('¡Registro exitoso!');
        redirect('/dashboard');
    }

    public function api()
    {
        $data = json_input();

        // Validar
        $validation = new PulseValidations();
        $validation->setData($data)
            ->required('email')
            ->email('email');

        if ($validation->hasErrors()) {
            json_error('Validación fallida', 400, $validation->getErrors());
        }

        json_response(['success' => true], 200);
    }
}
```

---

## 7. 📁 PulseFile - Upload de Archivos

### ¿Qué hace?
Maneja subida de imágenes y documentos con compresión y redimensionamiento.

### Métodos Disponibles

```php
$file = new PulseFile();

// ✅ Subir imagen
$result = $file->uploadImage('avatar', [
    'max_size' => 2 * 1024 * 1024,  // 2MB
    'allowed_types' => ['jpg', 'jpeg', 'png'],
    'compress' => true,
    'quality' => 85,
    'max_width' => 800,
    'max_height' => 600
]);

// ✅ Subir múltiples imágenes
$results = $file->uploadMultipleImages('gallery', [
    'max_size' => 5 * 1024 * 1024,  // 5MB
    'compress' => true,
    'quality' => 80
]);

// ✅ Subir documento
$docResult = $file->uploadDocument('contract', [
    'allowed_types' => ['pdf', 'doc', 'docx'],
    'max_size' => 20 * 1024 * 1024  // 20MB
]);

// ✅ Eliminar archivo
if ($file->deleteFile('archivo.jpg')) {
    // Eliminado exitosamente
}

// ✅ Obtener errores
$errors = $file->getErrors();
```

### Ejemplo Completo: Upload de Avatar

```php
class ProfileController extends PulseController
{
    /**
     * Mostrar formulario
     */
    public function edit()
    {
        $userId = $this->session->get('user_id');
        $userModel = $this->model('UserModel');
        $user = $userModel->getById($userId);

        $this->view('profile/edit', [
            'user' => $user,
            'csrf_token' => PulseCsrf::getTokenField()
        ]);
    }

    /**
     * Procesar actualización de perfil
     */
    public function update()
    {
        try {
            // 1. Verificar CSRF
            PulseCsrf::verifyToken($_POST);

            $userId = $this->session->get('user_id');

            // 2. Validar datos básicos
            $data = $_POST;

            $validation = new PulseValidations();
            $validation->setData($data)
                ->required('name')
                ->email('email')
                ->min('name', 2)
                ->max('name', 50);

            if ($validation->hasErrors()) {
                json_error('Validación fallida', 400, $validation->getErrors());
            }

            // 3. Actualizar datos en BD
            $userModel = $this->model('UserModel');
            $userModel->update($userId, [
                'name' => $data['name'],
                'email' => $data['email']
            ]);

            // 4. Procesar avatar si se subió
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = new PulseFile();
                $result = $file->uploadImage('avatar', [
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
                    'max_size' => 2 * 1024 * 1024,  // 2MB
                    'compress' => true,
                    'quality' => 85,
                    'max_width' => 300,
                    'max_height' => 300,
                    'prefix' => 'avatar_' . $userId . '_'
                ]);

                if ($result === false) {
                    $errors = $file->getErrors();
                    json_error('Error al subir avatar', 400, $errors);
                }

                // Actualizar avatar en BD
                $userModel->update($userId, [
                    'avatar' => $result['filename']
                ]);

                $this->session->set('user_avatar', $result['relative_path']);
            }

            // 5. Respuesta de éxito
            $updatedUser = $userModel->getById($userId);
            
            $this->function->jsonResponse('user', $updatedUser, 200, 'Perfil actualizado exitosamente');

        } catch (PulseErrorHandler $e) {
            $this->function->jsonError($e->getMessage(), 500);
        }
    }
}
```

### Ejemplo: Galería de Imágenes

```php
class GalleryController extends PulseController
{
    /**
     * Formulario de subida
     */
    public function uploadForm()
    {
        $this->view('gallery/upload', [
            'csrf_token' => PulseCsrf::getTokenField()
        ]);
    }

    /**
     * Procesar upload múltiple
     */
    public function upload()
    {
        try {
            // Verificar CSRF
            PulseCsrf::verifyToken($_POST);

            // Verificar si se subió al menos una imagen
            if (!isset($_FILES['images'])) {
                $this->session->flashError('Debes seleccionar al menos una imagen');
                redirect('/gallery/upload');
            }

            $file = new PulseFile();
            $results = $file->uploadMultipleImages('images', [
                'max_size' => 5 * 1024 * 1024,  // 5MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
                'compress' => true,
                'quality' => 80
            ]);

            // Verificar si todas las imágenes se subieron
            $allSuccess = !in_array(false, array_column($results, 'success'));

            if (!$allSuccess) {
                $errors = [];
                foreach ($results as $result) {
                    if (!$result['success']) {
                        $errors[] = $result;
                    }
                }

                $this->function->jsonError('Algunas imágenes fallaron', 207, $errors);
            }

            // Guardar en BD (ejemplo simulado)
            foreach ($results as $result) {
                if ($result['success']) {
                    // En producción: $galleryModel->addImage($result['filename']);
                }
            }

            $this->function->jsonResponse('images', $results, 200, 'Imágenes subidas exitosamente');

        } catch (PulseErrorHandler $e) {
            $this->function->jsonError($e->getMessage(), 500);
        }
    }
}
```

---

## 📋 Referencia Rápida

### PulseCsrf
| Método | Uso |
|--------|-----|
| `PulseCsrf::verifyToken($_POST)` | Verificar token POST |
| `PulseCsrf::getTokenField()` | Campo hidden en form |
| `PulseCsrf::regenerateToken()` | Después de login |
| `PulseCsrf::clearToken()` | Logout |

### PulseSessions
| Método | Uso |
|--------|-----|
| `$this->session->set('key', $value)` | Guardar dato |
| `$this->session->get('key')` | Obtener dato |
| `$this->session->has('key')` | Verificar si existe |
| `$this->session->destroy()` | Cerrar sesión |
| `$this->session->flashSuccess('msg')` | Mensaje éxito |

### PulseValidations
| Método | Uso |
|--------|-----|
| `->required('field')` | Campo requerido |
| `->email('field')` | Email válido |
| `->min('field', 3)` | Longitud mínima |
| `->max('field', 50)` | Longitud máxima |
| `->matches('a', 'b')` | Debe coincidir |

### PulseFile
| Método | Uso |
|--------|-----|
| `$file->uploadImage('field', $options)` | Subir imagen |
| `$file->uploadDocument('field', $options)` | Subir documento |
| `$file->deleteFile('filename')` | Eliminar archivo |

---

## 📚 Más Ejemplos

Para ejemplos completos de cada clase, revisa:
- `app/controllers/SystemClassesDemoController.php` - Ejemplos de todas las clases
- `docs/PULSECONTROLLER_REFACTOR.md` - Mejoras en PulseController
- `docs/HTML_VS_JSON.md` - Respuestas HTML vs JSON

---

**Pulse Framework - Sistema Completo y Bien Documentado**
