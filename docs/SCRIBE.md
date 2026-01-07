# Sistema de Migraciones Scribe 📝

**Scribe** es el sistema de migraciones incluido en Pulse Framework. Permite gestionar la estructura de la base de datos de forma versionada y reproducible.

## 📋 Comandos Disponibles

### Migraciones

```bash
# Ejecutar todas las migraciones pendientes
php scribe migrate

# Revertir el último batch de migraciones
php scribe rollback

# Crear una nueva migración
php scribe make:migration <nombre_tabla> [create|alter]
```

### Seeds

```bash
# Ejecutar todos los seeds pendientes
php scribe seed

# Crear un nuevo seed
php scribe make:seed <nombre_tabla>
```

## 🏗️ Crear Tablas

Usa el sistema fluido de Scribe para crear tablas:

```php
<?php
use System\Scribe\Table;

return Table::create('users')
    ->id()
    ->str('name', 100)
    ->str('email', 255)
    ->text('bio')
    ->tinyint('status')->default(1)
    ->stamp()  // Agrega created_at y updated_at automáticamente
    ->index('email')
    ->unique('email');
```

## 📝 Tipos de Columnas Disponibles

| Método | Descripción | Ejemplo |
|--------|-------------|---------|
| `str($name, $len)` | VARCHAR | `->str('name', 100)` |
| `text($name)` | TEXT | `->text('description')` |
| `int($name)` | INT | `->int('age')` |
| `bigint($name)` | BIGINT | `->bigint('user_id')` |
| `tinyint($name)` | TINYINT | `->tinyint('status')` |
| `decimal($name, $p, $s)` | DECIMAL | `->decimal('price', 10, 2)` |
| `date($name)` | DATE | `->date('birth_date')` |
| `datetime($name)` | DATETIME | `->datetime('published_at')` |
| `timestamp($name)` | TIMESTAMP | `->timestamp('created_at')` |
| `json($name)` | JSON | `->json('metadata')` |
| `enum($name, $values)` | ENUM | `->enum('role', ['admin', 'user'])` |

## 🔧 Modificadores de Columnas

```php
->nullable()           // Permite NULL
->default('value')     // Valor por defecto
->index()              // Agrega índice
->unique()             // Agrega índice único
->fk('tabla', 'col')   // Foreign Key
```

## ✨ Métodos Especiales

### `id()` - Primary Key Autoincrement

```php
->id()  // Crea: id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

### `stamp()` - Timestamps Automáticos

```php
->stamp()  // Crea:
           //   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
           //   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

## 🔄 Alterar Tablas

Para modificar tablas existentes, usa `Table::alter()`:

```php
<?php
use System\Scribe\Table;

return Table::alter('users')
    ->addColumn('avatar')->str(255)->nullable()
    ->addColumn('phone')->str(20)->nullable()->after('email')
    ->dropColumn('old_column');
```

## 🌱 Seeds (Datos de Ejemplo)

Crea un archivo de seed con datos iniciales:

```php
<?php
// database/seeds/01_users.seed.php

return [
    'table' => 'users',
    'data' => [
        [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'status' => 1,
        ],
        [
            'name' => 'María García',
            'email' => 'maria@example.com',
            'status' => 1,
        ],
    ],
];
```

## 💡 Ejemplos Completos

### Ejemplo 1: Tabla de Posts

```php
<?php
use System\Scribe\Table;

return Table::create('posts')
    ->id()
    ->unsignedInt('user_id')
    ->str('title', 200)
    ->text('content')
    ->str('slug', 255)
    ->tinyint('published')->default(0)
    ->timestamp('published_at')->nullable()
    ->stamp()
    ->index('slug')
    ->unique('slug')
    ->index('published')
    ->fk('users', 'id');
```

### Ejemplo 2: Tabla de Comentarios

```php
<?php
use System\Scribe\Table;

return Table::create('comments')
    ->id()
    ->unsignedInt('post_id')
    ->unsignedInt('user_id')
    ->text('content')
    ->tinyint('approved')->default(0)
    ->stamp()
    ->index('post_id')
    ->index('user_id')
    ->fk('posts', 'id')
    ->fk('users', 'id');
```

### Ejemplo 3: Alterar Tabla

```php
<?php
use System\Scribe\Table;

// Agregar columnas
return Table::alter('users')
    ->addColumn('avatar')->str(255)->nullable()
    ->addColumn('bio')->text()->nullable()
    ->addColumn('website')->str(255)->nullable();
```

## ⚙️ Configuración

El archivo `database/config.php` se conecta a la base de datos definida en `app/config/config.php`.

Asegúrate de configurar correctamente las credenciales en `app/config/config.php` antes de ejecutar migraciones.

## 📊 Tablas del Sistema

Scribe crea automáticamente las siguientes tablas de control:

- `_schema_history` - Historial de migraciones aplicadas
- `_seed_history` - Historial de seeds ejecutados

⚠️ **No modifies estas tablas manualmente**

## 🎯 Flujo de Trabajo Recomendado

1. **Crear migración**
   ```bash
   php scribe make:migration products create
   ```

2. **Editar el archivo** generado en `database/migrations/`

3. **Ejecutar migración**
   ```bash
   php scribe migrate
   ```

4. **Verificar en base de datos**
   ```sql
   SHOW TABLES;
   DESCRIBE products;
   ```

5. **Crear seed** (opcional)
   ```bash
   php scribe make:seed products
   ```

6. **Editar el archivo** generado en `database/seeds/`

7. **Ejecutar seed**
   ```bash
   php scribe seed
   ```

## ⚠️ Consideraciones Importantes

- **Rollback** solo elimina tablas completas (CREATE), no ALTER
- Para alteraciones complejas, crea migraciones de reversión manual
- Los nombres de tablas y columnas deben usar **snake_case**
- Los timestamps se manejan automáticamente con `stamp()`
- Los seeds se ejecutan solo una vez (controlado por `_seed_history`)

## 🔍 Debugging

Si tienes problemas:

```bash
# Ver estructura de migraciones
ls -la database/migrations/

# Ver estructura de seeds
ls -la database/seeds/

# Ver tablas del sistema en MySQL
SELECT * FROM _schema_history;
SELECT * FROM _seed_history;
```

---

**Scribe** - Gestión de base de datos hecha simple ✨
