
# Concept\Config - Developer Documentation

## 1. 🔧 Basics of Usage

### Initialization

```php
use Concept\Config\Config;
$config = new Config([
    'app' => ['name' => 'MyApp', 'debug' => true],
]);
```

### Reading & Writing

```php
$config->get('app.name');         // "MyApp"
$config->set('app.version', '1.0');
$config->has('app.debug');       // true
```

---

## 2. 🗂 Storage Layer: DotArray

### Access Types

```php
$config['database.host'];       // via ArrayAccess
$config->database->get('host'); // via object chain
```

### Nested Access & Child

```php
$child = $config->child('database');
$child->set('user', 'root'); // updates database.user
```

### Copy vs Reference

```php
$copy = $config->child('database', true);  // immutable
$ref  = $config->child('database', false); // reference
```

### Merge Methods

```php
$config->merge(['a' => ['b' => 1]], 'a');
$config->fill(['a' => ['c' => 2]], 'a');    // doesn't overwrite existing keys
$config->replace(['a' => ['b' => 999]], 'a');
```

---

## 3. 🧱 Architecture Overview

```
Config → Storage(DotArray) → Resource → Adapter → Parser → Plugin → Context
```

### Interfaces

- `ConfigInterface`
- `StorageInterface`
- `AdapterInterface`
- `PluginInterface`
- `ContextInterface`

---

## 4. 🧩 Plugin System

### Built-in Plugins

- **EnvPlugin**: `${env.DB_USER}`
- **IncludePlugin**: `{"include": "file.json"}`
- **ReferencePlugin**: `${config.some.value}`
- **ContextPlugin**: `${context.foo}`

### Custom Plugin

```php
class UppercasePlugin extends AbstractPlugin {
    public function resolve(mixed $value): mixed {
        return is_string($value) ? strtoupper($value) : $value;
    }
}
```

Registering:

```php
$config->getContext()->getPluginManager()->register(new UppercasePlugin());
```

---

## 5. 🔌 Adapters

### JSON Adapter

```php
$adapter = new JsonAdapter();
$data = $adapter->load(new Resource('config.json'));
```

### PHP Adapter

```php
$adapter = new PhpAdapter();
$data = $adapter->load(new Resource('config.php'));
```

### Custom Adapter

```php
class YamlAdapter implements AdapterInterface {
    public function load(ResourceInterface $resource): array {
        return yaml_parse_file($resource->getPath());
    }
}
```

---

## 6. 🧠 Resources

### Resource Types

```php
new Resource('/path/to/file.json'); // file
new Resource(['foo' => 'bar']);     // array source
```

Resources encapsulate input types and provide consistent access.

---

## 7. 🧰 Tools

### Parser

Handles plugin calls, token parsing, recursion.

### Resolver

```php
${config.path}
${env.HOME}
${context.locale}
```

---

## 8. 🧪 Practical Use Cases

### Load .json config with env & reference

```json
{
  "db": {
    "user": "${env.DB_USER}",
    "dsn": "mysql:host=localhost;dbname=${config.db.name}"
  },
  "db.name": "test"
}
```

### Programmatic Overrides

```php
$config->set('feature.enabled', true);
```

### Load, Modify, Merge

```php
$config->merge([
    'logger' => ['level' => 'debug']
], 'app');
```

---

## ✅ Summary

- Modular plugin-based system.
- Deep dot access via `DotArray`.
- Extendable with custom adapters/plugins.
- Supports JSON, PHP, ENV-based configurations.
- Built-in support for context-based variable resolution.
