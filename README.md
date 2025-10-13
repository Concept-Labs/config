
# Concept\Config

A powerful, flexible, and extensible configuration management library for PHP 8.2+.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Concept](https://img.shields.io/badge/Concept-Ecosystem-violet.svg)]([https://www.php.net/](https://github.com/Concept-Labs))

## 🌟 Features

- **Dot Notation Access**: Access nested configuration values using intuitive dot notation
- **Multiple Format Support**: JSON, PHP arrays, and extensible to YAML, INI, etc.
- **Plugin System**: Extensible plugin architecture for custom processing
- **Variable Interpolation**: Support for environment variables, references, and context values
- **Import/Include System**: Modular configuration with file imports
- **Context Management**: Flexible context for runtime variable resolution
- **Lazy Resolution**: Efficient lazy evaluation of configuration values
- **Factory Pattern**: Multiple factory methods for different use cases
- **Facade Interface**: Simplified configuration creation with pre-configured plugins
- **Type Safe**: Full PHP 8.2+ type hints and strict typing

## 📦 Installation

Install via Composer:

```bash
composer require concept-labs/config
```

## 🚀 Quick Start

### Basic Usage

```php
use Concept\Config\Config;

// Create a config instance with inline data
$config = new Config([
    'app' => [
        'name' => 'MyApp',
        'debug' => true,
        'version' => '1.0.0'
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306
    ]
]);

// Access values using dot notation
echo $config->get('app.name');        // "MyApp"
echo $config->get('database.host');   // "localhost"

// Set values
$config->set('app.version', '2.0.0');

// Check if a key exists
if ($config->has('app.debug')) {
    // ...
}
```

### Loading from Files

```php
use Concept\Config\Facade\Config;

// Use the Facade for fully-featured configuration with all plugins pre-configured
$config = Config::config('config/app.json', context: [
    'ENV' => getenv()
]);

// Or use the direct class
use Concept\Config\Config;

// Load from a JSON file
$config = new Config();
$config->load('config/app.json', parse: true);

// Or use the static factory
use Concept\Config\StaticFactory;

$config = StaticFactory::fromFile('config/app.json', parse: true);
```

### Using Context and Variables

```php
// Create config with environment variable support
$config = new Config([
    'database' => [
        'host' => '@env(DB_HOST)',
        'user' => '@env(DB_USER)',
        'password' => '@env(DB_PASSWORD)'
    ]
], context: [
    'ENV' => getenv()
]);

// Parse to resolve variables
$config->getParser()->parse($config->dataReference());
```

## 📚 Documentation

Comprehensive documentation is available in the [docs](./docs) directory:

- [Getting Started](./docs/getting-started.md) - Installation and basic usage
- [Architecture](./docs/architecture.md) - System architecture and design
- [Configuration Guide](./docs/configuration.md) - Configuration methods and options
- [Plugin System](./docs/plugins.md) - Understanding and creating plugins
- [Adapters](./docs/adapters.md) - File format adapters
- [Context & Variables](./docs/context.md) - Variable resolution and context management
- [API Reference](./docs/api-reference.md) - Complete API documentation
- [Examples](./docs/examples.md) - Practical examples and use cases
- [Advanced Topics](./docs/advanced.md) - Custom plugins, adapters, and factories

## 💡 Key Concepts

### Dot Notation

Access nested configuration values using familiar dot notation:

```php
$config->get('database.connection.host');
$config->set('cache.drivers.redis.port', 6379);
```

### Plugin System

Extend functionality through plugins:

```php
// Environment variables
'@env(DB_HOST)'

// Config references
'@database.host'

// Custom plugins
$config->getParser()->registerPlugin(MyCustomPlugin::class, priority: 100);
```

### Factories

Multiple ways to create configurations:

```php
// Facade - Simplified interface with pre-configured plugins
use Concept\Config\Facade\Config;

$config = Config::config('config/*.json', context: [
    'env' => 'production',
    'ENV' => getenv()
]);

// Static factory
use Concept\Config\StaticFactory;

$config = StaticFactory::create(['key' => 'value']);
$config = StaticFactory::fromFile('config.json');
$config = StaticFactory::fromGlob('config/*.json');

// Builder factory
use Concept\Config\Factory;

$config = (new Factory())
    ->withFile('config/app.json')
    ->withFile('config/database.json')
    ->withContext(['env' => 'production'])
    ->create();
```

### The Facade Interface

The `Concept\Config\Facade\Config` class provides a simplified, opinionated way to create configurations with all essential plugins pre-configured and ready to use.

```php
use Concept\Config\Facade\Config;

// Create config from file(s) with all plugins enabled
$config = Config::config(
    source: 'config/*.json',           // File path or glob pattern
    context: ['env' => 'production'],  // Optional context variables
    overrides: ['debug' => false]      // Optional configuration overrides
);

// The facade automatically configures these plugins in priority order:
// - EnvPlugin (999): @env(VAR_NAME) - Environment variables
// - ContextPlugin (998): ${context.key} - Context values  
// - IncludePlugin (997): @include(file) - Include external files
// - ImportPlugin (996): @import directive - Import and merge configs
// - ReferencePlugin (995): @path.to.value - Internal references
// - ConfigValuePlugin (994): Config-specific value resolution
```

**When to use the Facade:**
- You want a quick, fully-featured configuration setup
- You need environment variables, references, imports, and includes
- You prefer convention over configuration
- You're starting a new project and want sensible defaults

**When to use other factories:**
- `StaticFactory`: Simple use cases without plugins
- `Factory`: Custom plugin configuration and advanced control

## 🔧 Advanced Features

### Importing Configurations

```php
// Import from another file
$config->import('additional-config.json', parse: true);

// Import to a specific path
$config->importTo('database-config.json', 'database', parse: true);
```

### Configuration Nodes

```php
// Get a configuration subtree as a new Config instance
$dbConfig = $config->node('database');
echo $dbConfig->get('host'); // Direct access without 'database.' prefix
```

### Exporting

Export configuration to files with automatic format detection based on file extension:

```php
// Export to JSON (auto-detected from .json extension)
$config->export('output/config.json');

// Export to PHP array (auto-detected from .php extension)
$config->export('output/config.php');
```

The format is automatically determined by the Resource adapter system based on the file extension.

## 🧪 Examples

### Multi-Environment Configuration

```json
{
  "app": {
    "name": "MyApp",
    "env": "@env(APP_ENV)",
    "debug": "@env(APP_DEBUG)"
  },
  "database": {
    "host": "@env(DB_HOST)",
    "port": "@env(DB_PORT)",
    "name": "@env(DB_NAME)"
  }
}
```

### Configuration with References

```json
{
  "paths": {
    "root": "/var/www",
    "public": "@paths.root/public",
    "storage": "@paths.root/storage"
  }
}
```

### Importing Multiple Files

```json
{
  "@import": [
    "config/database.json",
    "config/cache.json",
    "config/services.json"
  ]
}
```

## 🛠️ Development

### Requirements

- PHP 8.2 or higher
- Composer

### Dependencies

- `concept-labs/arrays` - Array manipulation utilities

### Running Tests

This package includes comprehensive test coverage using both PHPUnit and Pest:

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature

# Run tests with coverage
composer test:coverage
```

**Test Statistics:**
- 143 tests covering all functionality
- 259 assertions
- PHPUnit and Pest test styles
- Unit and integration tests

See [tests/README.md](tests/README.md) for detailed testing documentation.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/Concept-Labs/config/issues)
- **Documentation**: [Full Documentation](./docs)

## 🙏 Credits

Developed and maintained by [Concept Labs](https://github.com/Concept-Labs).

---

**Made with ❤️ by Concept Labs**
=======
# Concept-Labs Configuration Package

A flexible configuration package for PHP projects.

**Storage and Context instances @see** 

```php
Concept\DotArray 
```
in
[GitHub Repository](https://github.com/concept-labs/arrays)

## Installation

```bash
composer install concept-labs/config
```

## Quick Start

```php
// Instantiate with data and context
new Config(array $data, array $context);

// Or use the static helper
Config::fromArray([...]);
```

## Storage

- `Concept\DotArray`

## Context

- `Concept\DotArray`

## Resource Plugins

Resource plugins are applied as needed (interpolator, import, include, env, etc.).

### Supported Resource Adapters

- JSON
- PHP
- (YAML can be added)

## Parser & Plugins

The parser uses a plugin system.

### Built-in Plugins

- **Context:** `${context_value}` or `${context_value|default}`
- **Expressions:**
    - **Env:** `@env(ENV_VAR)`
    - **Reference (Node):** `#path.to.node` or `#path.to.node|default`
    - **Reference (Value):** `#{path.to.value}` or `#{path.to.value|default}`
- **Import:** `{"@import": "source"}`
- **Extends:** `{"@extends": "path.to.node"}`
- **Include:** `{"node": "@include(source)"}`
- Custom plugins can be added

### Custom Plugins

Register custom plugins:

```php
Concept\Config::getParser()->registerPlugin(PluginInterface|callable|string $plugin, int $priority = 0): static
```

If the plugin name is a string, it will be instantiated from the class.

---

## Interface

```php
interface ConfigInterface extends IteratorAggregate
{
        public function reset(): static;

        /**
         * Create a new Config instance from an array
         */
        public static function fromArray(array $data, array $context = []): static;

        /**
         * Hydrate the configuration with data
         */
        public function hydrate(array $data): static;

        /**
         * Get the configuration data as a reference
         */
        public function &dataReference(): array;

        /**
         * Convert config to array
         */
        public function toArray(): array;

        /**
         * Convert config to dot array
         */
        public function dotArray(): DotArrayInterface;

        /**
         * Get a node by key
         */
        public function node(string $path, bool $copy = true): static;

        /**
         * Get a value by key
         */
        public function &get(string $key, mixed $default = null): mixed;

        /**
         * Set a value by key
         */
        public function set(string $key, mixed $value): static;

        /**
         * Check if a key exists
         */
        public function has(string $key): bool;

        // public function remove(string $key): static;

        /**
         * Load configuration from a source
         */
        public function load(string|array|ConfigInterface $source, bool $parse = false): static;

        /**
         * Import configuration from a source
         */
        public function import(string|array|ConfigInterface $source, bool $parse = false): static;

        /**
         * Import configuration to a specific path
         */
        public function importTo(string|array|ConfigInterface $source, string $path, bool $parse = false): static;

        /**
         * Export configuration to a target file
         */
        public function export(string $target): static;

        /**
         * Replace current context with new values
         */
        public function withContext(ContextInterface|array $context): static;

        /**
         * Get the context
         */
        public function getContext(): ContextInterface;

        /**
         * Get the resource instance
         */
        public function getResource(): ResourceInterface;

        /**
         * Get the storage instance
         */
        public function getParser(): ParserInterface;
}
```
