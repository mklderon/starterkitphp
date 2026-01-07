# Guía de Migración v2.0.0 → v2.1.0

## Resumen de Cambios

La versión 2.1.0 introduce un cambio arquitectónico mayor para mejorar la consistencia del framework:

✅ **Todas las clases del sistema ahora son servicios instanciados**
✅ **Eliminadas todas las llamadas estáticas a clases del sistema**
✅ **PulseCsrf y PulseErrorHandler convertidas de estáticas a instancias**

Este cambio hace que el código sea más fácil de entender y mantener.

---

## Cambios Arquitectónicos

### 1. PulseCsrf.php
**Antes (v2.0.0):**
```php
// Clase estática
PulseCsrf::generateToken();
PulseCsrf::verifyToken($_POST);
PulseCsrf::getTokenField();
```

**Ahora (v2.1.0):**
```php
// Instancia accesible desde PulseController
$this->csrf->generateToken();
$this->csrf->verifyToken($_POST);
$this->csrf->getTokenField();
```

---

### 2. PulseErrorHandler.php
**Antes (v2.0.0):**
```php
// Extendía Exception y tenía métodos estáticos
class PulseErrorHandler extends Exception
{
    public static function handleException(Exception $e) { }
    public static function handleRoutingException(Exception $e) { }
    // ...
}

// Uso:
PulseErrorHandler::handleException($e);
```

**Ahora (v2.1.0):**
```php
// Clase de servicio independiente
class PulseErrorHandler
{
    public function handleException(Exception $e) { }
    public function handleRoutingException(Exception $e) { }
    // ...
}

// Uso:
$this->errorHandler->handleException($e);
```

**Importante:** PulseErrorHandler ya NO extiende Exception, es un servicio de manejo de errores.

---

### 3. PulseController.php
**Nueva propiedad añadida:**
```php
protected $errorHandler;
```

**Constructor actualizado:**
```php
public function __construct()
{
    $this->csrf = new PulseCsrf();
    $this->function = new PulseFunctions();
    $this->session = new PulseSessions();
    $this->errorHandler = new PulseErrorHandler(); // ✅ NUEVO
}
```

---

## Cómo Migrar tu Código

### En tus Controladores

**Antes:**
```php
class MiController extends PulseController
{
    public function procesarFormulario()
    {
        // Verificar CSRF
        PulseCsrf::verifyToken($_POST);
    }
}
```

**Ahora:**
```php
class MiController extends PulseController
{
    public function procesarFormulario()
    {
        // Verificar CSRF (usar $this->csrf)
        $this->csrf->verifyToken($_POST);
    }
}
```

---

### En tus Vistas

**Antes:**
```php
<form method="POST">
    <?= PulseCsrf::getTokenField() ?>
</form>
```

**Ahora:**
```php
<form method="POST">
    <?= $this->csrf->getTokenField() ?>
</form>
```

**Nota:** En las vistas, `$this` hace referencia al controlador que renderizó la vista.

---

### Manejo de Excepciones

**Antes:**
```php
try {
    // Código que puede lanzar error
} catch (PulseErrorHandler $e) {
    PulseErrorHandler::handleGenericException($e);
}
```

**Ahora:**
```php
try {
    // Código que puede lanzar error
} catch (Exception $e) {
    $this->errorHandler->handleGenericException($e);
}
```

**Importante:**
- Ahora usas `catch (Exception $e)` en lugar de `catch (PulseErrorHandler $e)`
- PulseErrorHandler ya no es una Exception, es un servicio

---

## Archivos Actualizados en el Framework

Los siguientes archivos del framework han sido actualizados:

✅ `system/PulseCsrf.php` - Convertida de estática a instancia
✅ `system/PulseErrorHandler.php` - Convertida de estática a instancia, eliminada herencia de Exception
✅ `system/PulseController.php` - Agregada propiedad `$errorHandler`, instanciada en constructor
✅ `public/index.php` - Actualizado para usar instancia de PulseErrorHandler
✅ `system/PulseDatabase.php` - Corregido: ahora lanza Exception en lugar de PulseErrorHandler
✅ `system/PulseDispatcher.php` - Corregido: ahora lanza Exception en lugar de PulseErrorHandler
✅ `system/QueryBuilder.php` - Corregido: ahora lanza Exception en lugar de PulseErrorHandler
✅ `system/PulseRouter.php` - Corregido: ahora lanza Exception en lugar de PulseErrorHandler

---

## Código Ejemplo Completo

### Ejemplo 1: Procesar Formulario con CSRF

```php
<?php

class ContactoController extends PulseController
{
    public function index()
    {
        $this->view('contacto/index');
    }

    public function enviar()
    {
        try {
            // Verificar token CSRF
            $this->csrf->verifyToken($_POST);

            // Procesar formulario
            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $mensaje = $_POST['mensaje'] ?? '';

            // Validar
            if (empty($nombre) || empty($email) || empty($mensaje)) {
                throw new Exception('Todos los campos son obligatorios');
            }

            // Guardar en base de datos
            // ...

            // Mensaje de éxito
            $this->session->flashSuccess('Mensaje enviado correctamente');
            redirect('/contacto');

        } catch (Exception $e) {
            $this->errorHandler->handleGenericException($e);
        }
    }
}
```

### Ejemplo 2: API con Protección CSRF

```php
<?php

class ApiController extends PulseController
{
    public function crear()
    {
        try {
            // Obtener token del header o body
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            if (!$token) {
                json_error('Token CSRF requerido', 400);
            }

            // Verificar CSRF
            $this->csrf->verifyToken(['csrf_token' => $token]);

            // Procesar datos
            // ...

            json_response(['created' => true], 201, 'Recurso creado');

        } catch (Exception $e) {
            $this->errorHandler->handleGenericException($e);
        }
    }
}
```

### Ejemplo 3: Vista con CSRF

```php
<!-- app/views/contacto/index.php -->
<div class="contact-form">
    <h2>Contacto</h2>

    <?php if ($this->session->has('error')): ?>
        <div class="alert alert-danger">
            <?= $this->session->flash('error') ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->has('success')): ?>
        <div class="alert alert-success">
            <?= $this->session->flash('success') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/contacto/enviar">
        <!-- Campo CSRF token -->
        <?= $this->csrf->getTokenField() ?>

        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Mensaje:</label>
            <textarea name="mensaje" required></textarea>
        </div>

        <button type="submit">Enviar</button>
    </form>
</div>
```

---

## Métodos Disponibles

### PulseCsrf (acceso: `$this->csrf`)

```php
// Generar un nuevo token
$token = $this->csrf->generateToken();

// Verificar token (lanza Exception si es inválido)
$this->csrf->verifyToken($_POST);

// Obtener token actual (sin generar uno nuevo)
$token = $this->csrf->getToken();

// Obtener campo HTML del token
$html = $this->csrf->getTokenField();

// Regenerar token (útil después de login/logout)
$newToken = $this->csrf->regenerateToken();

// Limpiar token de la sesión
$this->csrf->clearToken();
```

### PulseErrorHandler (acceso: `$this->errorHandler`)

```php
// Manejar cualquier excepción
$this->errorHandler->handleException($exception);

// Manejar error de enrutamiento (404)
$this->errorHandler->handleRoutingException($exception);

// Manejar error de base de datos
$this->errorHandler->handleDatabaseException($exception);

// Manejar error genérico
$this->errorHandler->handleGenericException($exception);
```

---

## Beneficios de este Cambio

✅ **Consistencia:** TODAS las clases del sistema son servicios instanciados
✅ **Facilidad:** Todo se accede vía `$this->nombreServicio`
✅ **Testabilidad:** Las clases son más fáciles de probar (mock)
✅ **Mantenibilidad:** Código más limpio y predecible
✅ **Documentación:** PHPDoc refleja correctamente los tipos de excepciones

---

## Preguntas Frecuentes

### Q: ¿Por qué eliminar el enfoque estático?
A: El enfoque mixto (algunas clases estáticas, otras instancias) creaba inconsistencia. Ahora TODO es instanciado, lo que hace que el código sea más fácil de entender.

### Q: ¿Tengo que migrar mi código inmediatamente?
A: Sí, porque las clases estáticas ya no existen en v2.1.0. Tu código que use `PulseCsrf::` o `PulseErrorHandler::` ya no funcionará.

### Q: ¿PulseErrorHandler sigue siendo una Exception?
A: No. PulseErrorHandler ahora es un servicio de manejo de errores, no una Exception. Si quieres lanzar una excepción, usa `throw new Exception($mensaje)`.

### Q: ¿Cómo accedo a estos servicios desde las vistas?
A: Desde las vistas, `$this` hace referencia al controlador que renderizó la vista. Puedes usar `$this->csrf->metodo()` o `$this->errorHandler->metodo()`.

### Q: ¿Qué pasa con PulseFunctions, PulseSessions, PulseValidations, etc.?
A: Estas clases ya eran instanciadas en v2.0.0, por lo que no han cambiado. Solo PulseCsrf y PulseErrorHandler requirieron conversión.

---

## Checklist de Migración

- [ ] Reemplazar `PulseCsrf::` por `$this->csrf->` en todos los controladores
- [ ] Reemplazar `PulseCsrf::` por `$this->csrf->` en todas las vistas
- [ ] Reemplazar `PulseErrorHandler::` por `$this->errorHandler->` en todos los controladores
- [ ] Reemplazar `catch (PulseErrorHandler $e)` por `catch (Exception $e)`
- [ ] Reemplazar `throw new PulseErrorHandler(...)` por `throw new Exception(...)`
- [ ] Probar todos los formularios con CSRF
- [ ] Probar el manejo de errores (404, 500, etc.)
- [ ] Probar las APIs con protección CSRF

---

## Soporte

Si tienes dudas durante la migración, consulta:

- `docs/SYSTEM_CLASSES_GUIDE.md` - Guía completa de clases del sistema
- `docs/MIGRATION_GUIDE.md` - Este documento
- Revisa los ejemplos en `app/controllers/SystemClassesDemoController.php`

**Versión del Framework:** 2.1.0
**Fecha de Publicación:** Enero 2026
