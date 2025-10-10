# Configuration Guide

A comprehensive guide to configuring and using Concept\Config in your applications.

## Creating Configurations

### Direct Instantiation

```php
use Concept\Config\Config;

// With array data
$config = new Config([
    'app' => ['name' => 'MyApp'],
    'debug' => true
]);

// With context
$config = new Config(
    data: ['key' => 'value'],
    context: ['env' => 'production']
);
```

### Using Static Factory

```php
use Concept\Config\StaticFactory;

// From array
$config = StaticFactory::create(['key' => 'value']);

// From file
$config = StaticFactory::fromFile('config.json');

// From multiple files
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json'
]);

// From glob pattern
$config = StaticFactory::fromGlob('config/*.json');
```

### Using Builder Factory

```php
use Concept\Config\Factory;

$config = (new Factory())
    ->withFile('config/app.json')
    ->withGlob('config/modules/*.json')
    ->withArray(['override' => 'value'])
    ->withContext(['env' => 'staging'])
    ->create();
```

## Reading Configuration

### Get Values

```php
// Simple get
$value = $config->get('key');

// Nested get with dot notation
$value = $config->get('database.connection.host');

// With default value
$value = $config->get('missing.key', 'default');

// By reference (for modification)
$ref = &$config->get('some.path');
$ref = 'new value'; // Modifies the config
```

### Check Existence

```php
if ($config->has('database.host')) {
    // Key exists
}

// Check nested keys
if ($config->has('app.features.billing.enabled')) {
    // Nested key exists
}
```

### Iterate Configuration

```php
// Config implements IteratorAggregate
foreach ($config as $key => $value) {
    echo "$key = $value\n";
}

// Convert to array
$array = $config->toArray();
```

## Writing Configuration

### Set Values

```php
// Set simple value
$config->set('key', 'value');

// Set nested value
$config->set('database.host', 'localhost');

// Set complex structure
$config->set('services.cache', [
    'driver' => 'redis',
    'host' => '127.0.0.1',
    'port' => 6379
]);
```

### Using DotArray

Access the underlying storage for advanced operations:

```php
$storage = $config->dotArray();

// Merge data
$storage->merge(['new' => 'data'], 'path.to.merge');

// Fill (doesn't overwrite existing)
$storage->fill(['defaults' => 'here'], 'path');

// Replace
$storage->replace(['completely' => 'new'], 'path');
```

## Loading Configuration

### From Files

```php
// Load and replace all data
$config->load('config/app.json');

// Load with parsing
$config->load('config/app.json', parse: true);

// Load from PHP file
$config->load('config/app.php');
```

### From Arrays

```php
// Load array data
$config->load([
    'app' => ['name' => 'MyApp'],
    'debug' => true
]);
```

### From Another Config

```php
$otherConfig = new Config(['data' => 'here']);
$config->load($otherConfig);
```

## Importing Configuration

Import merges data into existing configuration:

```php
// Basic import
$config->import('additional.json');

// Import with parsing
$config->import('additional.json', parse: true);

// Import to specific path
$config->importTo('modules/auth.json', 'modules.auth', parse: true);
```

### Import Strategies

The import uses recursive merge by default:

```php
// Original config
$config = new Config([
    'app' => ['name' => 'MyApp', 'debug' => false]
]);

// Import
$config->import([
    'app' => ['debug' => true, 'version' => '2.0']
]);

// Result:
// [
//     'app' => [
//         'name' => 'MyApp',      // preserved
//         'debug' => true,         // overwritten
//         'version' => '2.0'       // added
//     ]
// ]
```

## Exporting Configuration

The `export()` method allows you to save your configuration to a file. The output format is automatically detected based on the file extension using the **Resource** adapter system.

### To Files

```php
// Export to JSON (uses JsonAdapter)
$config->export('output/config.json');

// Export to PHP (uses PhpAdapter)
$config->export('output/config.php');

// Export parsed configuration for production
$config = StaticFactory::fromGlob('config/*.json', parse: true);
$config->export('compiled/config.json');
```

### Format Auto-Detection

The `export()` method uses the **Resource** component with registered adapters to determine the output format:

- **`.json`** - Uses `JsonAdapter` to write JSON with pretty formatting
- **`.php`** - Uses `PhpAdapter` to write a PHP array (using `var_export`)

The adapter is automatically selected based on the file extension via `AdapterManager::getAdapter()`.

### How It Works

1. `Config::export($target)` calls `Resource::write($target, $data)`
2. `Resource::write()` uses `AdapterManager::getAdapter($target)` to find the appropriate adapter
3. The adapter checks if it supports the file using `Adapter::supports($uri)` (checks file extension)
4. The selected adapter writes the data in the appropriate format

### Custom Formats

You can add support for additional formats by registering custom adapters:

```php
$resource = $config->getResource();
$adapterManager = $resource->getAdapterManager();
$adapterManager->registerAdapter(YamlAdapter::class);

// Now you can export to YAML
$config->export('output/config.yaml');
```

### Get as Array

```php
// Get entire configuration
$data = $config->toArray();

// Get by reference (be careful!)
$ref = &$config->dataReference();
```

## Configuration Nodes

Nodes create isolated sub-configurations:

```php
$config = new Config([
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => ['host' => 'localhost'],
            'pgsql' => ['host' => 'postgres.local']
        ]
    ]
]);

// Get database node
$dbConfig = $config->node('database');
echo $dbConfig->get('default'); // 'mysql'
echo $dbConfig->get('connections.mysql.host'); // 'localhost'
```

### Copy vs Reference

```php
// Copy (default) - independent from parent
$copy = $config->node('database', copy: true);
$copy->set('default', 'pgsql');
echo $config->get('database.default'); // Still 'mysql'

// Reference - changes affect parent
$ref = $config->node('database', copy: false);
$ref->set('default', 'pgsql');
echo $config->get('database.default'); // Now 'pgsql'
```

## Working with Context

### Setting Context

```php
// During construction
$config = new Config(
    data: [],
    context: [
        'env' => 'production',
        'region' => 'us-east-1'
    ]
);

// After construction
$config->withContext([
    'env' => 'staging',
    'custom' => 'value'
]);

// From ContextInterface
$context = new Context(['key' => 'value']);
$config->withContext($context);
```

### Environment Variables

```php
use Concept\Config\Context\Context;

$context = new Context();
$context->withEnv(getenv()); // Add all environment variables

$config->withContext($context);
```

### Custom Sections

```php
$context = $config->getContext();
$context->withSection('database', [
    'host' => 'db.example.com',
    'port' => 3306
]);
```

### Accessing Context

```php
$context = $config->getContext();
$envValue = $context->get('ENV.PATH');
$customValue = $context->get('custom.section.key');
```

## Parsing Configuration

### When to Parse

Parse configuration when you need to resolve:
- Environment variables (`@env(...)`)
- References (`@some.path`)
- Imports (`@import`)
- Other directives

```php
// Parse during load
$config->load('config.json', parse: true);

// Parse during import
$config->import('additional.json', parse: true);

// Parse manually
$config->getParser()->parse($config->dataReference());
```

### Parse vs No Parse

```php
// Without parsing - raw data
$config->load('config.json', parse: false);
$value = $config->get('database.host'); // '@env(DB_HOST)' as string

// With parsing - resolved data
$config->load('config.json', parse: true);
$value = $config->get('database.host'); // Actual value from environment
```

## Advanced Configuration

### Hydrate

Replace configuration data while preserving the instance:

```php
$config = new Config(['old' => 'data']);

$config->hydrate(['new' => 'data']);
// $config now contains only ['new' => 'data']
```

### Reset

Reset configuration to initial empty state:

```php
$config->reset();
// All data, context, parser, and resource are reset
```

### Clone

Create a deep copy of configuration:

```php
$config = new Config(['data' => 'here']);
$clone = clone $config;

$clone->set('data', 'modified');
// Original $config is unchanged
```

## Resource Management

### Get Resource

Access the resource manager for advanced operations:

```php
$resource = $config->getResource();

// Read directly
$data = [];
$resource->read($data, 'config.json', withParser: true);

// Write directly
$resource->write('output.json', ['data' => 'here']);
```

### Adapter Management

```php
$resource = $config->getResource();
$adapterManager = $resource->getAdapterManager();

// Register custom adapter
$adapterManager->registerAdapter(CustomAdapter::class);

// Get adapter for file
$adapter = $adapterManager->getAdapter('config.yaml');
```

## Parser Management

### Get Parser

```php
$parser = $config->getParser();
```

### Register Plugins

```php
// Register with priority
$parser->registerPlugin(MyPlugin::class, priority: 100);

// Register callable
$parser->registerPlugin(
    function($value, $path, &$data, $next) {
        // Custom logic
        return $next($value, $path, $data);
    },
    priority: 50
);
```

### Get Plugin

```php
$plugin = $parser->getPlugin(EnvPlugin::class);
```

## Compilation

Compile multiple sources into a single configuration file:

```php
use Concept\Config\StaticFactory;

StaticFactory::compile(
    sources: [
        'config/base.json',
        'config/overrides.json',
        'config/local.json'
    ],
    context: ['env' => 'production'],
    target: 'compiled/config.json'
);
```

This is useful for:
- Deployment optimization
- Reducing file I/O
- Creating distribution packages

## Configuration Patterns

### Layered Configuration

```php
$config = (new Factory())
    ->withFile('config/defaults.json')     // Base defaults
    ->withFile('config/app.json')          // Application config
    ->withFile('config/env/prod.json')     // Environment-specific
    ->withFile('config/local.json')        // Local overrides
    ->create();
```

### Feature Flags

```php
$config = new Config([
    'features' => [
        'new_ui' => '@env(FEATURE_NEW_UI)',
        'beta_api' => '@env(FEATURE_BETA_API)',
        'analytics' => true
    ]
]);

$config->load($config->toArray(), parse: true);

if ($config->get('features.new_ui')) {
    // Enable new UI
}
```

### Service Configuration

```php
$config = new Config([
    'services' => [
        'database' => [
            'class' => 'Database\\Connection',
            'config' => '@database'
        ],
        'cache' => [
            'class' => 'Cache\\Redis',
            'config' => '@cache'
        ]
    ],
    'database' => [
        'host' => '@env(DB_HOST)',
        'port' => '@env(DB_PORT)'
    ],
    'cache' => [
        'host' => '@env(REDIS_HOST)',
        'port' => 6379
    ]
]);
```

### Multi-Tenant Configuration

```php
$tenant = 'acme-corp';

$config = (new Factory())
    ->withFile('config/base.json')
    ->withFile("config/tenants/$tenant.json")
    ->withContext(['tenant' => $tenant])
    ->create();
```

## Best Practices

### 1. Separate Concerns

```php
// Good: Separate files for different concerns
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json',
    'config/cache.json',
    'config/services.json'
]);
```

### 2. Use Environment Variables

```php
// Good: Sensitive data from environment
{
    "database": {
        "host": "@env(DB_HOST)",
        "password": "@env(DB_PASSWORD)"
    }
}
```

### 3. Default Values

```php
// Good: Provide sensible defaults
$timeout = $config->get('api.timeout', 30);
$retries = $config->get('api.retries', 3);
```

### 4. Validate Configuration

```php
// Good: Validate on load
$config->load('config.json', parse: true);

$required = ['app.name', 'database.host'];
foreach ($required as $key) {
    if (!$config->has($key)) {
        throw new \RuntimeException("Missing: $key");
    }
}
```

### 5. Use Type Checking

```php
// Good: Verify types
$debug = $config->get('app.debug');
if (!is_bool($debug)) {
    throw new \TypeError("app.debug must be boolean");
}
```

### 6. Immutable Where Possible

```php
// Good: Use copies for independent configs
$apiConfig = $config->node('api', copy: true);
// Changes to $apiConfig won't affect $config
```

### 7. Cache Compiled Configs

```php
// Good: Compile for production
if ($env === 'production') {
    if (!file_exists('cache/config.json')) {
        StaticFactory::compile(
            'config/*.json',
            ['env' => 'production'],
            'cache/config.json'
        );
    }
    $config = StaticFactory::fromFile('cache/config.json');
}
```
