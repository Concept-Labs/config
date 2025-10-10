# API Reference

Complete API documentation for Concept\Config.

## ConfigInterface

Main configuration interface.

**Namespace**: `Concept\Config`

### Methods

#### `reset(): static`

Reset the configuration to its initial empty state.

```php
$config->reset();
```

---

#### `fromArray(array $data, array $context = []): static`

Create a new Config instance from an array.

**Parameters**:
- `$data` - Configuration data
- `$context` - Context data (optional)

**Returns**: New Config instance

```php
$config = Config::fromArray(['key' => 'value']);
```

---

#### `hydrate(array $data): static`

Replace configuration data while preserving the instance.

**Parameters**:
- `$data` - New configuration data

**Returns**: Self for chaining

```php
$config->hydrate(['new' => 'data']);
```

---

#### `dataReference(): array`

Get a reference to the configuration data array.

**Returns**: Reference to data array

```php
$ref = &$config->dataReference();
```

---

#### `toArray(): array`

Convert configuration to array.

**Returns**: Configuration as array

```php
$array = $config->toArray();
```

---

#### `dotArray(): DotArrayInterface`

Get the underlying DotArray storage.

**Returns**: DotArray instance

```php
$storage = $config->dotArray();
```

---

#### `node(string $path, bool $copy = true): static`

Get a configuration subtree as a new Config instance.

**Parameters**:
- `$path` - Dot notation path
- `$copy` - Copy (true) or reference (false)

**Returns**: New Config instance

```php
$dbConfig = $config->node('database');
$dbConfigRef = $config->node('database', false);
```

---

#### `get(string $path, mixed $default = null): mixed`

Get a configuration value.

**Parameters**:
- `$path` - Dot notation path
- `$default` - Default value if not found

**Returns**: Configuration value or default

```php
$value = $config->get('app.name', 'DefaultApp');
```

---

#### `set(string $path, mixed $value): static`

Set a configuration value.

**Parameters**:
- `$path` - Dot notation path
- `$value` - Value to set

**Returns**: Self for chaining

```php
$config->set('app.version', '2.0.0');
```

---

#### `has(string $path): bool`

Check if a configuration key exists.

**Parameters**:
- `$path` - Dot notation path

**Returns**: True if exists, false otherwise

```php
if ($config->has('database.host')) {
    // ...
}
```

---

#### `load(string|array|ConfigInterface $source, bool $parse = false): static`

Load configuration, replacing existing data.

**Parameters**:
- `$source` - File path, array, or Config instance
- `$parse` - Parse after loading

**Returns**: Self for chaining

```php
$config->load('config.json', parse: true);
```

---

#### `import(string|array|ConfigInterface $source, bool $parse = false): static`

Import and merge configuration.

**Parameters**:
- `$source` - File path, array, or Config instance
- `$parse` - Parse after importing

**Returns**: Self for chaining

```php
$config->import('additional.json', parse: true);
```

---

#### `importTo(string|array|ConfigInterface $source, string $path, bool $parse = false): static`

Import configuration to a specific path.

**Parameters**:
- `$source` - File path, array, or Config instance
- `$path` - Target path
- `$parse` - Parse after importing

**Returns**: Self for chaining

```php
$config->importTo('db.json', 'database', parse: true);
```

---

#### `export(string $target): static`

Export configuration to a file.

**Parameters**:
- `$target` - Target file path

**Returns**: Self for chaining

```php
$config->export('output/config.json');
```

---

#### `withContext(ContextInterface|array $context): static`

Set or replace the configuration context.

**Parameters**:
- `$context` - Context instance or array

**Returns**: Self for chaining

```php
$config->withContext(['env' => 'production']);
```

---

#### `getContext(): ContextInterface`

Get the context instance.

**Returns**: ContextInterface

```php
$context = $config->getContext();
```

---

#### `getResource(): ResourceInterface`

Get the resource instance.

**Returns**: ResourceInterface

```php
$resource = $config->getResource();
```

---

#### `getParser(): ParserInterface`

Get the parser instance.

**Returns**: ParserInterface

```php
$parser = $config->getParser();
```

---

#### `getIterator(): Traversable`

Get iterator for foreach loops.

**Returns**: Traversable

```php
foreach ($config as $key => $value) {
    // ...
}
```

---

## StaticFactory

Static factory methods for creating configurations.

**Namespace**: `Concept\Config`

### Methods

#### `create(array $data = [], array $context = [], bool $parse = false): ConfigInterface`

Create a config instance.

```php
$config = StaticFactory::create(['key' => 'value']);
```

---

#### `fromFile(string $source, array $context = [], bool $parse = false): ConfigInterface`

Create from a file.

```php
$config = StaticFactory::fromFile('config.json', parse: true);
```

---

#### `fromFiles(array $sources, array $context = [], bool $parse = false): ConfigInterface`

Create from multiple files.

```php
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/db.json'
]);
```

---

#### `fromGlob(string $pattern, array $context = [], bool $parse = false): ConfigInterface`

Create from glob pattern.

```php
$config = StaticFactory::fromGlob('config/*.json');
```

---

#### `compile(string|array $sources, array $context, string $target): ConfigInterface`

Compile multiple sources to a single file.

```php
StaticFactory::compile(
    'config/*.json',
    ['env' => 'production'],
    'compiled.json'
);
```

---

## Factory

Builder pattern factory for configurations.

**Namespace**: `Concept\Config`

### Methods

#### `reset(): static`

Reset factory to initial state.

```php
$factory->reset();
```

---

#### `create(): ConfigInterface`

Create the configuration.

```php
$config = $factory->create();
```

---

#### `export(string $target): static`

Export configuration to file.

```php
$factory->export('output.json');
```

---

#### `getContext(): ContextInterface`

Get the context.

```php
$context = $factory->getContext();
```

---

#### `withContext(ContextInterface|array $context): static`

Set context.

```php
$factory->withContext(['env' => 'production']);
```

---

#### `withFile(string $file): static`

Add a file source.

```php
$factory->withFile('config/app.json');
```

---

#### `withGlob(string $pattern): static`

Add glob pattern source.

```php
$factory->withGlob('config/*.json');
```

---

#### `withArray(array $data): static`

Add array data.

```php
$factory->withArray(['key' => 'value']);
```

---

#### `withPlugin(PluginInterface|callable $plugin, int $priority = 0): static`

Register a plugin.

```php
$factory->withPlugin(MyPlugin::class, priority: 100);
```

---

#### `withParsing(bool $parse): static`

Enable/disable parsing.

```php
$factory->withParsing(true);
```

---

## ParserInterface

Parser interface for processing configuration.

**Namespace**: `Concept\Config\Parser`

### Methods

#### `registerPlugin(PluginInterface|callable|string $plugin, int $priority = 0): static`

Register a plugin.

```php
$parser->registerPlugin(MyPlugin::class, priority: 100);
```

---

#### `getPlugin(string $plugin): PluginInterface|callable`

Get a registered plugin.

```php
$plugin = $parser->getPlugin(EnvPlugin::class);
```

---

#### `parse(array &$data, bool $resolveNow = true): static`

Parse configuration data.

```php
$parser->parse($config->dataReference());
```

---

## PluginInterface

Plugin interface for custom processors.

**Namespace**: `Concept\Config\Parser\Plugin`

### Methods

#### `__invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed`

Process a configuration value.

**Parameters**:
- `$value` - Current value
- `$path` - Dot notation path
- `$subjectData` - Reference to all data
- `$next` - Next plugin in chain

**Returns**: Processed value

```php
public function __invoke($value, $path, &$data, $next): mixed
{
    // Process value
    return $next($value, $path, $data);
}
```

---

## ResourceInterface

Resource interface for I/O operations.

**Namespace**: `Concept\Config\Resource`

### Methods

#### `read(array &$data, string|array $source, bool $withParser = true): static`

Read configuration from source.

```php
$resource->read($data, 'config.json', withParser: true);
```

---

#### `write(mixed $target, array $data): static`

Write configuration to target.

```php
$resource->write('output.json', $data);
```

---

## AdapterInterface

Adapter interface for file formats.

**Namespace**: `Concept\Config\Resource`

### Methods

#### `supports(string $uri): bool` (static)

Check if adapter supports URI.

```php
JsonAdapter::supports('config.json'); // true
```

---

#### `read(string $uri): array`

Read from file.

```php
$data = $adapter->read('config.json');
```

---

#### `write(string $target, array $data): static`

Write to file.

```php
$adapter->write('output.json', $data);
```

---

#### `encode(array $data): string`

Encode array to string.

```php
$json = $adapter->encode(['key' => 'value']);
```

---

#### `decode(string $data): array`

Decode string to array.

```php
$array = $adapter->decode('{"key":"value"}');
```

---

## ContextInterface

Context interface.

**Namespace**: `Concept\Config\Context`

### Methods

#### `withEnv(array $env): static`

Add environment variables.

```php
$context->withEnv(getenv());
```

---

#### `withSection(string $section, array $data): static`

Add a context section.

```php
$context->withSection('app', ['name' => 'MyApp']);
```

---

## DotArrayInterface

Dot array interface (from concept-labs/arrays).

### Methods

#### `get(string $path, mixed $default = null): mixed`

Get value by dot path.

---

#### `set(string $path, mixed $value): static`

Set value by dot path.

---

#### `has(string $path): bool`

Check if path exists.

---

#### `merge(array $data, ?string $path = null): static`

Merge data.

---

#### `fill(array $data, ?string $path = null): static`

Fill without overwriting.

---

#### `replace(array $data, ?string $path = null): static`

Replace data.

---

## Exception Classes

### ConfigException

Base configuration exception.

**Namespace**: `Concept\Config\Exception`

```php
throw new ConfigException("Error message");
```

---

### InvalidArgumentException

Invalid argument exception.

**Namespace**: `Concept\Config\Exception`

```php
throw new InvalidArgumentException("Invalid value");
```

---

### RuntimeException

Runtime exception.

**Namespace**: `Concept\Config\Exception`

```php
throw new RuntimeException("Runtime error");
```

---

### ParserException

Parser exception.

**Namespace**: `Concept\Config\Parser\Exception`

```php
throw new ParserException("Parse error");
```

---

### ResourceException

Resource exception.

**Namespace**: `Concept\Config\Resource\Exception`

```php
throw new ResourceException("Resource error");
```

---

## Type Definitions

### Common Types

```php
// Config data
array<string, mixed>

// Context data
array<string, mixed>

// Plugin priority
int (higher = earlier execution)

// Dot notation path
string (e.g., 'database.connection.host')

// Source
string|array|ConfigInterface
```

### Return Types

- `static` - Returns self for method chaining
- `mixed` - Any type
- `array` - PHP array
- `bool` - Boolean true/false
- `string` - String value
- `int` - Integer value

## Constants

No constants are defined in the public API.

## Traits

### ConfigurableTrait

Helper trait for classes that use configuration.

**Namespace**: `Concept\Config\Contract`

```php
use Concept\Config\Contract\ConfigurableTrait;

class MyClass
{
    use ConfigurableTrait;
    
    public function doSomething()
    {
        $value = $this->getConfig()->get('key');
    }
}

$instance = new MyClass();
$instance->setConfig($config);
```

**Methods**:
- `setConfig(ConfigInterface $config): static`
- `getConfig(): ConfigInterface`
- `hasConfig(): bool`
