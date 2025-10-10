# Architecture

Understanding the architecture of Concept\Config will help you make the most of its features and extend it effectively.

## System Overview

Concept\Config is built on a modular, plugin-based architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────┐
│                  Config                         │
│  (Main entry point and orchestrator)            │
└────────┬────────────────────────────────────────┘
         │
         ├─────► Storage (DotArray)
         │       └─ Manages data with dot notation
         │
         ├─────► Context
         │       └─ Runtime variable resolution
         │
         ├─────► Resource
         │       ├─ Handles I/O operations
         │       └─► AdapterManager
         │           └─► Adapters (JSON, PHP, etc.)
         │
         └─────► Parser
                 ├─ Processes directives & variables
                 └─► Plugins (Env, Reference, Import, etc.)
```

## Core Components

### 1. Config (`Config`)

**Location**: `src/Config.php`

The main configuration class that orchestrates all components.

**Responsibilities**:
- Provide public API for configuration access
- Coordinate between storage, parser, and resource
- Manage configuration lifecycle (load, import, export)

**Key Methods**:
```php
interface ConfigInterface
{
    public function get(string $path, mixed $default = null): mixed;
    public function set(string $path, mixed $value): static;
    public function has(string $path): bool;
    public function load(string|array|ConfigInterface $source, bool $parse = false): static;
    public function import(string|array|ConfigInterface $source, bool $parse = false): static;
    public function export(string $target): static;
    public function node(string $path, bool $copy = true): static;
}
```

### 2. Storage (`Storage`)

**Location**: `src/Storage/Storage.php`

Extends `DotArray` from `concept-labs/arrays` to provide dot notation access to configuration data.

**Responsibilities**:
- Store configuration data
- Provide dot notation access
- Support nested operations

**Features**:
- Array access via `ArrayAccess` interface
- Nested path manipulation
- Reference vs copy semantics

### 3. Context (`Context`)

**Location**: `src/Context/Context.php`

Manages runtime context for variable resolution.

**Responsibilities**:
- Store context variables (environment, custom values)
- Provide access to context data during parsing
- Support sections (ENV, custom sections)

**Example**:
```php
$context = new Context();
$context->withEnv(getenv());
$context->withSection('custom', ['key' => 'value']);
```

### 4. Resource (`Resource`)

**Location**: `src/Resource/Resource.php`

Handles all I/O operations for configuration files.

**Responsibilities**:
- Read configuration from files/sources
- Write configuration to files
- Manage source stack (for circular reference detection)
- Coordinate with adapters

**Key Features**:
- Adapter selection based on file extension
- Circular reference detection
- Relative path resolution
- Fragment support (e.g., `file.json#path.to.data`)

### 5. Parser (`Parser`)

**Location**: `src/Parser/Parser.php`

Processes configuration data through a plugin system.

**Responsibilities**:
- Execute plugins in priority order
- Build middleware stack
- Handle pre-processing and post-processing
- Manage lazy resolution queue

**Plugin Execution**:
```php
// Plugins are executed in priority order (higher number = higher priority)
$parser->registerPlugin(EnvPlugin::class, 999);
$parser->registerPlugin(ReferencePlugin::class, 998);
$parser->registerPlugin(ImportPlugin::class, 997);
```

## Plugin System

### Plugin Architecture

Plugins implement `PluginInterface` and are executed as middleware:

```php
interface PluginInterface
{
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed;
}
```

### Built-in Plugins

#### 1. **EnvPlugin** (Priority: 999)
- **Location**: `src/Parser/Plugin/Expression/EnvPlugin.php`
- **Pattern**: `@env(VARIABLE_NAME)`
- **Purpose**: Resolve environment variables

#### 2. **ReferencePlugin** (Priority: 998)
- **Location**: `src/Parser/Plugin/Expression/ReferencePlugin.php`
- **Pattern**: `@path.to.value`
- **Purpose**: Resolve references to other config values

#### 3. **ImportPlugin** (Priority: 997)
- **Location**: `src/Parser/Plugin/Directive/ImportPlugin.php`
- **Pattern**: `"@import": "file.json"` or `"@import": ["file1.json", "file2.json"]`
- **Purpose**: Import and merge external configuration files

#### 4. **ContextPlugin** (Priority: 998)
- **Location**: `src/Parser/Plugin/ContextPlugin.php`
- **Pattern**: Context-based value resolution
- **Purpose**: Resolve values from context

#### 5. **CommentPlugin** (Priority: 996)
- **Location**: `src/Parser/Plugin/Directive/CommentPlugin.php`
- **Pattern**: `"@comment": "text"`
- **Purpose**: Remove comment directives from configuration

### Plugin Execution Flow

```
1. Parser.parse() called
   │
   ├─> 2. preProcess() - Build middleware stack
   │
   ├─> 3. Process each value through plugin chain
   │   │
   │   ├─> Plugin 1 (highest priority)
   │   │   └─> calls next()
   │   │
   │   ├─> Plugin 2
   │   │   └─> calls next()
   │   │
   │   └─> Plugin N (lowest priority)
   │       └─> returns value
   │
   └─> 4. postProcess() - Execute deferred operations
```

## Adapter System

### Adapter Architecture

Adapters handle reading and writing specific file formats.

```php
interface AdapterInterface
{
    public static function supports(string $uri): bool;
    public function read(string $uri): array;
    public function write(string $target, array $data): static;
    public function encode(array $data): string;
    public function decode(string $data): array;
}
```

### Built-in Adapters

#### 1. **JsonAdapter**
- **Location**: `src/Resource/Adapter/JsonAdapter.php`
- **Supports**: `.json` files
- **Features**: 
  - Glob pattern support
  - Priority-based merging
  - Pretty printing

#### 2. **PhpAdapter**
- **Location**: `src/Resource/Adapter/PhpAdapter.php`
- **Supports**: `.php` files
- **Features**:
  - Returns array from PHP file
  - Uses `var_export()` for writing

### Adapter Manager

**Location**: `src/Resource/AdapterManager.php`

Manages adapter registration and selection:

```php
$manager = new AdapterManager();
$manager->registerAdapter(JsonAdapter::class);
$manager->registerAdapter(PhpAdapter::class);

$adapter = $manager->getAdapter('config.json'); // Returns JsonAdapter
```

## Factory Pattern

The library provides three factory approaches for different use cases.

### Facade

**Location**: `src/Facade/Config.php`

The Facade provides an opinionated, production-ready configuration setup with all essential plugins pre-configured:

```php
namespace Concept\Config\Facade;

class Config
{
    public static function config(
        array|string $source, 
        array $context = [], 
        array $overrides = []
    ): ConfigInterface;
}
```

**Pre-configured Plugins** (in execution order):
1. **EnvPlugin** (priority 999) - Environment variable resolution (`@env(VAR)`)
2. **ContextPlugin** (priority 998) - Context value resolution (`${context.key}`)
3. **IncludePlugin** (priority 997) - File content inclusion (`@include(file)`)
4. **ImportPlugin** (priority 996) - Configuration imports (`{"@import": "file"}`)
5. **ReferencePlugin** (priority 995) - Internal references (`@path.to.value`)
6. **ConfigValuePlugin** (priority 994) - Config value processing

**Example**:
```php
use Concept\Config\Facade\Config;

$config = Config::config(
    source: 'config/*.json',
    context: ['env' => 'production', 'ENV' => getenv()],
    overrides: ['debug' => false]
);
```

**When to Use**:
- Quick setup with sensible defaults
- Need all standard plugins (env, references, imports)
- Starting new projects
- Convention over configuration

### StaticFactory

**Location**: `src/StaticFactory.php`

Provides static factory methods for common use cases:

```php
class StaticFactory
{
    public static function create(array $data = [], array $context = []): ConfigInterface;
    public static function fromFile(string $source, array $context = []): ConfigInterface;
    public static function fromFiles(array $sources, array $context = []): ConfigInterface;
    public static function fromGlob(string $pattern, array $context = []): ConfigInterface;
    public static function compile(string|array $sources, array $context, string $target): ConfigInterface;
}
```

**When to Use**:
- Simple configurations without plugins
- Direct file loading
- Minimal overhead
- Custom plugin setup needed

### Builder Factory

**Location**: `src/Factory.php`

Fluent builder for complex configuration setups:

```php
class Factory
{
    public function withFile(string $file): static;
    public function withGlob(string $pattern): static;
    public function withArray(array $data): static;
    public function withContext(ContextInterface|array $context): static;
    public function withPlugin(PluginInterface|callable $plugin, int $priority = 0): static;
    public function create(): ConfigInterface;
}
```

**When to Use**:
- Need custom plugin configuration
- Complex multi-source setups
- Fine-grained control over plugin priorities
- Advanced use cases

**Comparison Table**:

| Feature | Facade | StaticFactory | Builder Factory |
|---------|--------|---------------|-----------------|
| Ease of Use | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Pre-configured Plugins | ✅ Yes | ❌ No | ❌ No |
| Custom Plugins | ❌ No | ❌ No | ✅ Yes |
| Multiple Sources | ✅ Glob | ✅ Yes | ✅ Yes |
| Context Support | ✅ Yes | ✅ Yes | ✅ Yes |
| Overrides | ✅ Yes | ❌ No | ✅ Yes |
| Best For | Quick setup | Simple configs | Advanced control |

## Data Flow

### Loading Configuration

```
1. Config::load('config.json', parse: true)
   │
   ├─> 2. Resource::read()
   │   │
   │   ├─> 3. AdapterManager::getAdapter('config.json')
   │   │   └─> Returns JsonAdapter
   │   │
   │   ├─> 4. JsonAdapter::read('config.json')
   │   │   └─> Returns array data
   │   │
   │   └─> 5. Parser::parse($data) [if parse=true]
   │       │
   │       └─> 6. Execute plugin chain
   │           └─> Resolve variables, imports, etc.
   │
   └─> 7. Storage::hydrate($data)
       └─> Store in DotArray
```

### Importing Configuration

```
1. Config::import('additional.json', parse: true)
   │
   ├─> 2. Resource::read() into temporary array
   │
   ├─> 3. Parser::parse() [if parse=true]
   │
   └─> 4. Storage::replace()
       └─> Merge with recursive strategy
```

### Exporting Configuration

```
1. Config::export('output.json')
   │
   ├─> 2. Storage::toArray()
   │   └─> Get configuration data
   │
   └─> 3. Resource::write('output.json', $data)
       │
       ├─> 4. AdapterManager::getAdapter('output.json')
       │   └─> Returns JsonAdapter
       │
       └─> 5. JsonAdapter::write('output.json', $data)
           └─> Encode and write to file
```

## Extension Points

### Custom Plugins

Create custom plugins by implementing `PluginInterface` or extending `AbstractPlugin`:

```php
use Concept\Config\Parser\Plugin\AbstractPlugin;

class MyPlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        // Your custom logic
        if (is_string($value) && str_starts_with($value, '@custom(')) {
            // Process custom directive
            $value = $this->processCustom($value);
        }
        
        return $next($value, $path, $subjectData);
    }
}

// Register
$config->getParser()->registerPlugin(MyPlugin::class, priority: 100);
```

### Custom Adapters

Create custom adapters by implementing `AdapterInterface`:

```php
use Concept\Config\Resource\AdapterInterface;

class YamlAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'yaml';
    }
    
    public function read(string $uri): array
    {
        return yaml_parse_file($uri);
    }
    
    public function write(string $target, array $data): static
    {
        file_put_contents($target, yaml_emit($data));
        return $this;
    }
    
    // ... implement encode/decode
}

// Register
$config->getResource()->getAdapterManager()->registerAdapter(YamlAdapter::class);
```

### Custom Storage

Extend storage for custom data handling:

```php
use Concept\Config\Storage\Storage;

class CachedStorage extends Storage
{
    private $cache;
    
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->cache->remember($path, function() use ($path, $default) {
            return parent::get($path, $default);
        });
    }
}
```

## Design Principles

### 1. Separation of Concerns
- Each component has a single, well-defined responsibility
- Clear interfaces between components

### 2. Open/Closed Principle
- Open for extension through plugins and adapters
- Closed for modification of core functionality

### 3. Dependency Injection
- Dependencies injected through constructors
- Testable and mockable components

### 4. Lazy Evaluation
- Parse only when needed
- Resolve references on-demand

### 5. Immutability Options
- Support both copy and reference semantics
- Choose based on use case

## Performance Considerations

### Parsing Strategy

```php
// Parse immediately (eager)
$config->load('config.json', parse: true);

// Parse later (lazy)
$config->load('config.json', parse: false);
$config->getParser()->parse($config->dataReference());
```

### Circular Reference Prevention

The Resource component maintains a source stack to detect and prevent circular imports:

```php
// In Resource::read()
if ($this->hasSource($source)) {
    throw new InvalidArgumentException('Circular reference detected');
}
```

### Plugin Priority

Higher priority plugins execute first. Use this to control execution order:

```php
// Environment variables resolved first
$parser->registerPlugin(EnvPlugin::class, 999);

// Then references
$parser->registerPlugin(ReferencePlugin::class, 998);

// Finally imports
$parser->registerPlugin(ImportPlugin::class, 997);
```

## Thread Safety

The library is designed for single-threaded PHP environments. For multi-threaded scenarios:
- Create separate Config instances per thread
- Use immutable copies (`node($path, copy: true)`)
- Avoid shared state in custom plugins
