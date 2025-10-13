# Plugin System

The plugin system is the heart of Concept\Config's extensibility. Plugins allow you to process, transform, and resolve configuration values during parsing.

## Overview

Plugins are middleware components that process configuration values in a pipeline. They execute in priority order (higher priority first) and can:

- Transform values
- Resolve variables and references
- Import external data
- Validate configuration
- Add custom directives

## Plugin Interface

```php
namespace Concept\Config\Parser\Plugin;

interface PluginInterface
{
    /**
     * Process a configuration value
     * 
     * @param mixed $value The current value being processed
     * @param string $path The dot-notation path to this value
     * @param array &$subjectData Reference to the entire config array
     * @param callable $next Call to continue to the next plugin
     * @return mixed The processed value
     */
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed;
}
```

## Built-in Plugins

### EnvPlugin

**Priority**: 999  
**Location**: `src/Parser/Plugin/Expression/EnvPlugin.php`

Resolves environment variables.

**Syntax**:
```php
'@env(VARIABLE_NAME)'
```

**Example**:
```json
{
  "database": {
    "host": "@env(DB_HOST)",
    "port": "@env(DB_PORT)",
    "user": "@env(DB_USER)",
    "password": "@env(DB_PASSWORD)"
  }
}
```

**Resolution Order**:
1. `$_ENV['VARIABLE_NAME']`
2. `getenv('VARIABLE_NAME')`
3. `context.ENV.VARIABLE_NAME`
4. Original value (if not found)

**Usage**:
```php
// Set environment variables
putenv('DB_HOST=localhost');
$_ENV['DB_PORT'] = 3306;

$config = new Config([
    'db' => [
        'host' => '@env(DB_HOST)',
        'port' => '@env(DB_PORT)'
    ]
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('db.host'); // 'localhost'
echo $config->get('db.port'); // 3306
```

### ReferenceNodePlugin

**Priority**: 998  
**Location**: `src/Parser/Plugin/ReferenceNodePlugin.php`

Resolves references to entire configuration nodes. When a string value matches `#path.to.node`, it's replaced with the entire value (scalar, array, or object) from that configuration path.

**Syntax**:
```php
'#path.to.node'
'#path.to.node|default'  // With fallback default value
```

**Example**:
```json
{
  "database": {
    "host": "localhost",
    "port": 3306,
    "credentials": {
      "user": "admin",
      "password": "secret"
    }
  },
  "services": {
    "api": {
      "connection": "#database.credentials"
    }
  }
}
```

**Usage**:
```php
$config = new Config([
    'paths' => [
        'root' => '/var/www/app',
        'storage' => '/var/www/app/storage'
    ],
    'backup' => [
        'location' => '#paths.storage'
    ]
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('backup.location'); // '/var/www/app/storage'
```

**With Default Values**:
```php
$config = new Config([
    'fallback' => '#missing.path|/default/path'
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('fallback'); // '/default/path'
```

### ReferenceValuePlugin

**Priority**: 998  
**Location**: `src/Parser/Plugin/ReferenceValuePlugin.php`

Interpolates configuration values within strings using the `#{...}` syntax. This allows you to embed references within larger strings.

**Lazy Resolution**: References are resolved lazily using a `Resolver`. This means the actual value lookup happens when you access the config value, not during parsing. This is important because referenced values might not exist yet when the parser encounters the reference.

**Syntax**:
```php
'text #{path.to.value} more text'
'#{path.to.value|default}'  // With fallback default value
```

**Example**:
```json
{
  "app": {
    "name": "MyApp",
    "version": "1.0.0"
  },
  "server": {
    "host": "localhost",
    "port": 8080
  },
  "api": {
    "url": "http://#{server.host}:#{server.port}/api",
    "title": "#{app.name} v#{app.version}"
  },
  "paths": {
    "root": "/var/www",
    "public": "#{paths.root}/public",
    "cache": "#{paths.root}/storage/cache"
  }
}
```

**Usage**:
```php
$config = new Config([
    'server' => [
        'host' => 'localhost',
        'port' => 8080
    ],
    'connection' => [
        'url' => 'http://#{server.host}:#{server.port}/api'
    ]
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('connection.url'); // 'http://localhost:8080/api'
```

**With Default Values**:
```php
$config = new Config([
    'message' => 'Hello #{user.name|Guest}!',
    'api' => 'http://#{host|localhost}:#{port|8080}/api'
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('message'); // 'Hello Guest!'
echo $config->get('api');      // 'http://localhost:8080/api'
```

**Note**: ReferenceValuePlugin only works with scalar values. If you reference an array or object, it will produce an error message. When values are interpolated into strings, they are converted to string type. The plugin uses lazy resolution, so referenced values are looked up when you call `get()`, not during parsing.

### ImportPlugin

**Priority**: 997  
**Location**: `src/Parser/Plugin/Directive/ImportPlugin.php`

Imports and merges external configuration files.

**Syntax**:
```json
{
  "@import": "file.json"
}

// Or multiple files
{
  "@import": ["file1.json", "file2.json"]
}

// With merge mode
{
  "@import:combine": "file.json",
  "@import:overwrite": "override.json",
  "@import:preserve": "defaults.json"
}
```

**Merge Modes**:
- `combine` (default): Recursively merge arrays
- `overwrite`: Overwrite existing values
- `preserve`: Keep existing values, ignore imports

**Example**:

`config/database.json`:
```json
{
  "connections": {
    "mysql": {
      "host": "localhost",
      "port": 3306
    }
  }
}
```

`config/app.json`:
```json
{
  "@import": "database.json",
  "app": {
    "name": "MyApp"
  }
}
```

**Result**:
```json
{
  "connections": {
    "mysql": {
      "host": "localhost",
      "port": 3306
    }
  },
  "app": {
    "name": "MyApp"
  }
}
```

**Glob Support**:
```json
{
  "@import": "config/modules/*.json"
}
```

### ExtendsPlugin

**Priority**: 997  
**Location**: `src/Parser/Plugin/Directive/ExtendsPlugin.php`

Extends a configuration node with properties from another node, creating inheritance-like behavior.

**Syntax**:
```json
{
  "@extends": "path.to.node"
}
```

**How it works**:
- The `@extends` directive copies all properties from the referenced node into the current node
- Existing properties in the current node are preserved (not overwritten)
- The `@extends` directive is removed after processing
- Supports forward references (can reference nodes defined later in the configuration)

**Example**:

```php
$config = new Config([
    'database' => [
        'defaults' => [
            'host' => 'localhost',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ]
    ],
    'production' => [
        '@extends' => 'database.defaults',
        'host' => 'prod.example.com',
        'password' => 'secret'
    ]
]);

$config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
$config->getParser()->parse($config->dataReference());

echo $config->get('production.host');     // 'prod.example.com' (overridden)
echo $config->get('production.port');     // 3306 (inherited)
echo $config->get('production.charset');  // 'utf8mb4' (inherited)
echo $config->get('production.password'); // 'secret' (own property)
```

**Result**:
```json
{
  "database": {
    "defaults": {
      "host": "localhost",
      "port": 3306,
      "charset": "utf8mb4"
    }
  },
  "production": {
    "host": "prod.example.com",
    "port": 3306,
    "charset": "utf8mb4",
    "password": "secret"
  }
}
```

**Real-world example** (Multiple environments):
```php
$config = new Config([
    'app' => [
        'base' => [
            'debug' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
            'cache' => [
                'driver' => 'file'
            ]
        ]
    ],
    'environments' => [
        'development' => [
            '@extends' => 'app.base',
            'debug' => true,
            'cache' => [
                'driver' => 'array'
            ]
        ],
        'production' => [
            '@extends' => 'app.base',
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'prod_'
            ]
        ],
        'testing' => [
            '@extends' => 'app.base',
            'debug' => true
        ]
    ]
]);
```

**Forward References**:
The plugin supports forward references using lazy resolution:

```php
$config = new Config([
    'child' => [
        '@extends' => 'base',
        'extra' => 'value'
    ],
    'base' => [
        'prop1' => 'value1',
        'prop2' => 'value2'
    ]
]);

$config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
$config->getParser()->parse($config->dataReference());

echo $config->get('child.prop1'); // 'value1'
echo $config->get('child.extra'); // 'value'
```

**Working with @import and @include**:

The `@extends` plugin is fully compatible with `@import` and `@include` directives:

*Example 1: Extends in included file*
```php
// db.json
{
  "@extends": "defaults.db",
  "dsn": "mysql:localhost"
}

// config.json
{
  "defaults": {
    "db": {
      "db_name": "foo"
    }
  },
  "db": "@include(db.json)"
}
```

*Example 2: Extends referencing imported data*
```php
// loggers.json
{
  "my-logger": {
    "preference": "My\\Logger\\FileLogger"
  }
}

// config.json
{
  "@import": "loggers.json",
  "logger": {
    "@extends": "my-logger",
    "path": "/var/log/"
  }
}
```

*Example 3: Extends before import (forward reference)*
```php
{
  "logger": {
    "@extends": "my-logger",  // References data loaded later
    "path": "/var/log/"
  },
  "@import": "loggers.json"    // Loads my-logger definition
}
```

All these scenarios work correctly because:
- In nested parsing (from @import/@include), @extends resolves immediately within the local context
- In top-level parsing, @extends uses lazy resolution for forward references
- The parser tracks parse depth to handle nested imports correctly

**Important Notes**:
- The referenced path must point to an array/object, not a scalar value
- If the referenced path doesn't exist, an `InvalidArgumentException` is thrown
- Properties in the extending node always take precedence over inherited properties
- The base node remains unchanged after extension

### ContextPlugin

**Priority**: 998  
**Location**: `src/Parser/Plugin/ContextPlugin.php`

Resolves values from the configuration context using the `${...}` syntax. Now supports default values with `${variable|default}` syntax.

**Syntax**: 
```php
'${path.to.context.value}'
'${variable|default}'  // With fallback default value
```

**Example**:
```php
$config = new Config(
    data: [
        'app' => [
            'env' => '${environment}',
            'region' => '${region}',
            'database' => 'app_${tenant_id}'
        ]
    ],
    context: [
        'environment' => 'production',
        'region' => 'us-east-1',
        'tenant_id' => 'acme'
    ]
);

$config->getParser()->parse($config->dataReference());

echo $config->get('app.env');      // 'production'
echo $config->get('app.region');   // 'us-east-1'
echo $config->get('app.database'); // 'app_acme'
```

**With Default Values**:
```php
$config = new Config(
    data: [
        'app' => [
            'mode' => '${mode|development}',
            'debug' => '${debug|false}'
        ]
    ],
    context: []  // Empty context
);

$config->getParser()->parse($config->dataReference());

echo $config->get('app.mode');  // 'development' (uses default)
echo $config->get('app.debug'); // 'false' (uses default)
```

**Note**: Context variables can be used inline within strings and support multiple replacements in the same value. If a context variable is not found and no default is provided, it will show an error message.

### CommentPlugin

**Priority**: 996  
**Location**: `src/Parser/Plugin/Directive/CommentPlugin.php`

Removes comment directives from configuration.

**Syntax**:
```json
{
  "@comment": "This is a comment",
  "actualConfig": "value"
}
```

## Reference Syntax Summary

The plugin system provides three different syntaxes for referencing values:

| Syntax | Plugin | Purpose | Example |
|--------|--------|---------|---------|
| `@env(VAR)` | EnvPlugin | Access environment variables | `@env(DB_HOST)` |
| `${var}` or `${var\|default}` | ContextPlugin | Access context data with optional defaults | `${region}` or `${mode\|dev}` |
| `#path.to.node` or `#path\|default` | ReferenceNodePlugin | Reference entire config nodes | `#database.config` |
| `#{path}` or `#{path\|default}` | ReferenceValuePlugin | Interpolate values in strings | `http://#{host}:#{port}` |

## Creating Custom Plugins

### Basic Plugin

```php
use Concept\Config\Parser\Plugin\AbstractPlugin;

class UppercasePlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        // Transform string values to uppercase
        if (is_string($value) && str_starts_with($value, '@upper(')) {
            preg_match('/@upper\((.*?)\)/', $value, $matches);
            $value = strtoupper($matches[1]);
        }
        
        // Call next plugin in chain
        return $next($value, $path, $subjectData);
    }
}
```

**Register the plugin**:
```php
$config->getParser()->registerPlugin(UppercasePlugin::class, priority: 100);
```

**Use it**:
```php
$config = new Config([
    'message' => '@upper(hello world)'
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('message'); // 'HELLO WORLD'
```

### Advanced Plugin with Configuration

```php
class PrefixPlugin extends AbstractPlugin
{
    public function __construct(
        ConfigInterface $config,
        private string $prefix = ''
    ) {
        parent::__construct($config);
    }
    
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && str_starts_with($value, '@prefix')) {
            $value = $this->prefix . substr($value, 7); // Remove '@prefix'
        }
        
        return $next($value, $path, $subjectData);
    }
}

// Register with configuration
$plugin = new PrefixPlugin($config, 'APP_');
$config->getParser()->registerPlugin($plugin, priority: 100);
```

### Plugin with External Dependencies

```php
class DatabaseLookupPlugin extends AbstractPlugin
{
    public function __construct(
        ConfigInterface $config,
        private PDO $database
    ) {
        parent::__construct($config);
    }
    
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && preg_match('/@db\((.*?)\)/', $value, $matches)) {
            $query = $matches[1];
            $stmt = $this->database->query("SELECT value FROM config WHERE key = " . 
                $this->database->quote($query));
            $value = $stmt->fetchColumn() ?: $value;
        }
        
        return $next($value, $path, $subjectData);
    }
}

// Register
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$plugin = new DatabaseLookupPlugin($config, $pdo);
$config->getParser()->registerPlugin($plugin, priority: 100);
```

### Validation Plugin

```php
class ValidationPlugin extends AbstractPlugin
{
    private array $errors = [];
    
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        // Validate required fields
        if ($path === 'database.host' && empty($value)) {
            $this->errors[] = "database.host is required";
        }
        
        if ($path === 'database.port' && !is_int($value)) {
            $this->errors[] = "database.port must be an integer";
        }
        
        return $next($value, $path, $subjectData);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}

// Use
$validator = new ValidationPlugin($config);
$config->getParser()->registerPlugin($validator, priority: 1000);
$config->getParser()->parse($config->dataReference());

if ($errors = $validator->getErrors()) {
    throw new \RuntimeException(implode(', ', $errors));
}
```

## Plugin Registration

### Register by Class Name

```php
$parser->registerPlugin(MyPlugin::class, priority: 100);
```

The parser will instantiate the plugin, passing the Config instance.

### Register Instance

```php
$plugin = new MyPlugin($config);
$parser->registerPlugin($plugin, priority: 100);
```

### Register Callable

```php
$parser->registerPlugin(
    function($value, $path, &$data, $next) {
        // Process value
        if (is_string($value)) {
            $value = trim($value);
        }
        return $next($value, $path, $data);
    },
    priority: 100
);
```

### Priority System

Plugins execute in priority order (highest first):

```php
// This executes first
$parser->registerPlugin(FirstPlugin::class, priority: 1000);

// This executes second
$parser->registerPlugin(SecondPlugin::class, priority: 500);

// This executes last
$parser->registerPlugin(LastPlugin::class, priority: 100);
```

**Default Priorities**:
- EnvPlugin: 999
- ContextPlugin: 998
- ReferenceNodePlugin: 998
- ReferenceValuePlugin: 998
- ImportPlugin: 997
- ExtendsPlugin: 997
- CommentPlugin: 996

Choose priorities above or below these based on when you need your plugin to execute.

## Plugin Patterns

### Transformation Plugin

```php
class JsonDecodePlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && str_starts_with($value, '@json(')) {
            preg_match('/@json\((.*?)\)/', $value, $matches);
            $value = json_decode($matches[1], true);
        }
        
        return $next($value, $path, $subjectData);
    }
}
```

### Conditional Plugin

```php
class EnvironmentPlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_array($value) && isset($value['@env'])) {
            $env = getenv('APP_ENV') ?: 'production';
            $value = $value[$env] ?? $value['production'] ?? null;
        }
        
        return $next($value, $path, $subjectData);
    }
}

// Use
$config = new Config([
    'cache' => [
        '@env' => [
            'development' => ['driver' => 'array'],
            'production' => ['driver' => 'redis']
        ]
    ]
]);
```

### Aggregation Plugin

```php
class MergePlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_array($value) && isset($value['@merge'])) {
            $sources = (array)$value['@merge'];
            $merged = [];
            foreach ($sources as $source) {
                $merged = array_merge($merged, $this->loadSource($source));
            }
            $value = $merged;
        }
        
        return $next($value, $path, $subjectData);
    }
}
```

## Best Practices

### 1. Keep Plugins Focused

Each plugin should do one thing well:

```php
// Good: Single responsibility
class EnvPlugin extends AbstractPlugin { /* ... */ }
class ReferencePlugin extends AbstractPlugin { /* ... */ }

// Bad: Too many responsibilities
class EverythingPlugin extends AbstractPlugin { /* ... */ }
```

### 2. Use Appropriate Priorities

```php
// Good: Resolve environment variables first
$parser->registerPlugin(EnvPlugin::class, 999);

// Then resolve references
$parser->registerPlugin(ReferencePlugin::class, 998);
```

### 3. Always Call Next

```php
// Good: Always call next
public function __invoke($value, $path, &$data, $next): mixed
{
    // Your logic
    return $next($value, $path, $data);
}

// Bad: Breaking the chain
public function __invoke($value, $path, &$data, $next): mixed
{
    return $value; // Chain broken!
}
```

### 4. Handle Edge Cases

```php
public function __invoke($value, $path, &$data, $next): mixed
{
    // Check type before processing
    if (!is_string($value)) {
        return $next($value, $path, $data);
    }
    
    // Check pattern before expensive operations
    if (!str_contains($value, '@mypattern')) {
        return $next($value, $path, $data);
    }
    
    // Process
    $value = $this->process($value);
    
    return $next($value, $path, $data);
}
```

### 5. Document Your Plugins

```php
/**
 * Resolves @secret(...) directives by fetching from secret store
 * 
 * Syntax: @secret(SECRET_NAME)
 * 
 * Example:
 *   "password": "@secret(DB_PASSWORD)"
 * 
 * Priority: Should run after EnvPlugin (< 999)
 */
class SecretPlugin extends AbstractPlugin
{
    // ...
}
```

### 6. Make Plugins Testable

```php
class MyPlugin extends AbstractPlugin
{
    // Extract logic to testable methods
    protected function shouldProcess(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, '@mypattern');
    }
    
    protected function processValue(string $value): string
    {
        // Testable transformation logic
        return strtoupper($value);
    }
    
    public function __invoke($value, $path, &$data, $next): mixed
    {
        if ($this->shouldProcess($value)) {
            $value = $this->processValue($value);
        }
        return $next($value, $path, $data);
    }
}
```

## Debugging Plugins

### Plugin Execution Order

```php
class DebugPlugin extends AbstractPlugin
{
    public function __invoke($value, $path, &$data, $next): mixed
    {
        error_log("Processing: $path = " . json_encode($value));
        $result = $next($value, $path, $data);
        error_log("Result: $path = " . json_encode($result));
        return $result;
    }
}

// Register with high priority to see all processing
$parser->registerPlugin(DebugPlugin::class, priority: 10000);
```

### Logging Plugin

```php
class LoggingPlugin extends AbstractPlugin
{
    public function __construct(
        ConfigInterface $config,
        private LoggerInterface $logger
    ) {
        parent::__construct($config);
    }
    
    public function __invoke($value, $path, &$data, $next): mixed
    {
        $this->logger->debug("Processing config path: $path", [
            'value' => $value,
            'type' => gettype($value)
        ]);
        
        return $next($value, $path, $data);
    }
}
```

## Common Use Cases

### Secrets Management

```php
class VaultPlugin extends AbstractPlugin
{
    public function __invoke($value, $path, &$data, $next): mixed
    {
        if (is_string($value) && preg_match('/@vault\((.*?)\)/', $value, $matches)) {
            $secretPath = $matches[1];
            $value = $this->fetchFromVault($secretPath);
        }
        return $next($value, $path, $data);
    }
    
    private function fetchFromVault(string $path): string
    {
        // Fetch from HashiCorp Vault, AWS Secrets Manager, etc.
        return $this->vaultClient->read($path);
    }
}
```

### Feature Flags

```php
class FeatureFlagPlugin extends AbstractPlugin
{
    public function __invoke($value, $path, &$data, $next): mixed
    {
        if (is_string($value) && str_starts_with($value, '@feature(')) {
            preg_match('/@feature\((.*?)\)/', $value, $matches);
            $value = $this->isFeatureEnabled($matches[1]);
        }
        return $next($value, $path, $data);
    }
}
```

### Template Rendering

```php
class TemplatePlugin extends AbstractPlugin
{
    public function __invoke($value, $path, &$data, $next): mixed
    {
        if (is_string($value) && str_contains($value, '{{')) {
            $value = $this->renderTemplate($value, $data);
        }
        return $next($value, $path, $data);
    }
    
    private function renderTemplate(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            fn($m) => RecursiveDotApi::get($data, trim($m[1])) ?? $m[0],
            $template
        );
    }
}
```
