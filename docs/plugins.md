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

### ReferencePlugin

**Priority**: 998  
**Location**: `src/Parser/Plugin/Expression/ReferencePlugin.php`

Resolves references to other configuration values.

**Syntax**:
```php
'@path.to.value'
```

**Example**:
```json
{
  "paths": {
    "root": "/var/www",
    "public": "@paths.root/public",
    "storage": "@paths.root/storage",
    "cache": "@paths.storage/cache"
  },
  "urls": {
    "api": "https://api.example.com",
    "cdn": "@urls.api/cdn"
  }
}
```

**Usage**:
```php
$config = new Config([
    'app' => [
        'name' => 'MyApp',
        'title' => '@app.name Dashboard'
    ]
]);

$config->getParser()->parse($config->dataReference());

echo $config->get('app.title'); // 'MyApp Dashboard'
```

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

### ContextPlugin

**Priority**: 998  
**Location**: `src/Parser/Plugin/ContextPlugin.php`

Resolves values from the configuration context.

**Example**:
```php
$config = new Config(
    data: [
        'app' => [
            'env' => '@context.environment',
            'region' => '@context.region'
        ]
    ],
    context: [
        'environment' => 'production',
        'region' => 'us-east-1'
    ]
);

$config->getParser()->parse($config->dataReference());

echo $config->get('app.env');    // 'production'
echo $config->get('app.region'); // 'us-east-1'
```

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

### ConfigValuePlugin

**Priority**: 994  
**Location**: `src/Parser/Plugin/ConfigValuePlugin.php`

Resolves config-specific value references.

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
- ReferencePlugin: 998
- ImportPlugin: 997
- CommentPlugin: 996
- ConfigValuePlugin: 994

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
