# Getting Started

This guide will help you get started with Concept\Config, from installation to your first configuration.

## Installation

### Requirements

- PHP 8.2 or higher
- Composer

### Install via Composer

```bash
composer require concept-labs/config
```

### Verify Installation

Create a simple test file to verify the installation:

```php
<?php
require_once 'vendor/autoload.php';

use Concept\Config\Config;

$config = new Config(['test' => 'Hello, Config!']);
echo $config->get('test'); // Output: Hello, Config!
```

## Basic Concepts

### Configuration Object

The `Config` class is the main entry point for working with configurations. It implements `ConfigInterface` and provides a fluent API for managing configuration data.

```php
use Concept\Config\Config;

$config = new Config([
    'app' => [
        'name' => 'MyApp',
        'version' => '1.0.0'
    ]
]);
```

### Dot Notation

Access nested values using dot notation:

```php
// Get values
$name = $config->get('app.name');     // "MyApp"
$version = $config->get('app.version'); // "1.0.0"

// Set values
$config->set('app.debug', true);
$config->set('database.host', 'localhost');

// Check existence
if ($config->has('app.name')) {
    // Key exists
}
```

### Default Values

Provide default values when accessing non-existent keys:

```php
$timeout = $config->get('api.timeout', 30); // Returns 30 if not set
```

## Loading from Files

### JSON Files

Create a configuration file `config/app.json`:

```json
{
  "app": {
    "name": "MyApplication",
    "version": "2.0.0",
    "debug": false
  },
  "database": {
    "host": "localhost",
    "port": 3306,
    "name": "mydb"
  }
}
```

Load it in your code:

```php
use Concept\Config\Config;

$config = new Config();
$config->load('config/app.json');

echo $config->get('app.name'); // "MyApplication"
```

### PHP Files

Create a configuration file `config/app.php`:

```php
<?php
return [
    'app' => [
        'name' => 'MyApplication',
        'version' => '2.0.0',
        'debug' => false
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306
    ]
];
```

Load it:

```php
$config = new Config();
$config->load('config/app.php');
```

## Using the Facade

The `Concept\Config\Facade\Config` class is the recommended way to create configurations for most use cases. It comes with all essential plugins pre-configured, ready to handle environment variables, references, imports, and more.

### Basic Facade Usage

```php
use Concept\Config\Facade\Config;

// Create from file with all features enabled
$config = Config::config('config/app.json');

// Access values immediately - variables are auto-resolved
echo $config->get('app.name');
```

### Facade with Context

```php
use Concept\Config\Facade\Config;

// Provide context for variable resolution
$config = Config::config(
    source: 'config/app.json',
    context: [
        'env' => 'production',
        'region' => 'us-east-1',
        'ENV' => getenv()  // Make all environment variables available
    ]
);
```

### Facade with Multiple Files

```php
use Concept\Config\Facade\Config;

// Load multiple files using glob patterns
$config = Config::config('config/*.json', context: [
    'ENV' => getenv()
]);
```

### Facade with Overrides

```php
use Concept\Config\Facade\Config;

// Override specific values
$config = Config::config(
    source: 'config/app.json',
    context: ['ENV' => getenv()],
    overrides: [
        'app.debug' => true,
        'cache.enabled' => false
    ]
);
```

### Pre-configured Plugins

The Facade automatically includes these plugins:

| Plugin | Priority | Purpose | Example |
|--------|----------|---------|---------|
| EnvPlugin | 999 | Environment variables | `@env(DB_HOST)` |
| ContextPlugin | 998 | Context values | `${region}` |
| IncludePlugin | 997 | Include file content | `@include(file.json)` |
| ImportPlugin | 996 | Import & merge configs | `{"@import": "db.json"}` |
| ReferencePlugin | 995 | Internal references | `@database.host` |
| ConfigValuePlugin | 994 | Config value resolution | Advanced parsing |

## Using Static Factory

The `StaticFactory` class provides convenient static methods for creating configurations:

### Create from Array

```php
use Concept\Config\StaticFactory;

$config = StaticFactory::create([
    'key' => 'value'
]);
```

### Create from File

```php
$config = StaticFactory::fromFile('config/app.json');
```

### Create from Multiple Files

```php
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json',
    'config/cache.json'
]);
```

### Create from Glob Pattern

```php
// Load all JSON files in the config directory
$config = StaticFactory::fromGlob('config/*.json');
```

## Working with Context

Context provides a way to pass runtime values that can be referenced in your configuration:

```php
$config = new Config(
    data: [
        'app' => [
            'name' => '@env(APP_NAME)',
            'url' => '@env(APP_URL)'
        ]
    ],
    context: [
        'ENV' => getenv()
    ]
);

// Parse to resolve environment variables
$config->getParser()->parse($config->dataReference());

echo $config->get('app.name'); // Value from APP_NAME env variable
```

## Parsing Configuration

The parser resolves variables, includes, and other directives in your configuration:

### Enable Parsing on Load

```php
// Parse immediately when loading
$config->load('config/app.json', parse: true);
```

### Manual Parsing

```php
// Load without parsing
$config->load('config/app.json', parse: false);

// Parse later
$config->getParser()->parse($config->dataReference());
```

## Importing Additional Configuration

You can merge additional configuration files into an existing config:

```php
$config = new Config(['app' => ['name' => 'MyApp']]);

// Import and merge
$config->import('config/additional.json', parse: true);
```

## Exporting Configuration

Export your configuration to a file. The output format is automatically detected based on the file extension:

```php
// Export to JSON (format auto-detected from .json extension)
$config->export('output/config.json');

// Export to PHP array (format auto-detected from .php extension)
$config->export('output/config.php');
```

## Configuration Nodes

Create isolated configuration instances from a subset of data:

```php
$config = new Config([
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'credentials' => [
            'username' => 'admin',
            'password' => 'secret'
        ]
    ]
]);

// Get a node for the database configuration
$dbConfig = $config->node('database');

// Access without the 'database.' prefix
echo $dbConfig->get('host');                    // "localhost"
echo $dbConfig->get('credentials.username');    // "admin"
```

### Copy vs Reference

```php
// Copy (default) - changes don't affect original
$dbConfigCopy = $config->node('database', copy: true);
$dbConfigCopy->set('host', 'remote.host');
echo $config->get('database.host'); // Still "localhost"

// Reference - changes affect original
$dbConfigRef = $config->node('database', copy: false);
$dbConfigRef->set('host', 'remote.host');
echo $config->get('database.host'); // Now "remote.host"
```

## Next Steps

Now that you understand the basics, explore these topics:

- [Architecture](./architecture.md) - Understand the system design
- [Configuration Guide](./configuration.md) - Learn all configuration methods
- [Plugin System](./plugins.md) - Extend with custom plugins
- [Context & Variables](./context.md) - Advanced variable resolution
- [Examples](./examples.md) - Real-world use cases

## Common Patterns

### Application Configuration

```php
// config/app.php
return [
    'name' => getenv('APP_NAME') ?: 'MyApp',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
];

// In your application
$config = StaticFactory::fromFile('config/app.php');
```

### Multi-Environment Setup

```php
$env = getenv('APP_ENV') ?: 'production';

$config = StaticFactory::fromFiles([
    'config/app.json',              // Base configuration
    "config/env/$env.json",         // Environment-specific
]);
```

### Configuration with Validation

```php
$config = StaticFactory::fromFile('config/app.json');

// Validate required keys
$required = ['app.name', 'app.version', 'database.host'];
foreach ($required as $key) {
    if (!$config->has($key)) {
        throw new \RuntimeException("Missing required config: $key");
    }
}
```
