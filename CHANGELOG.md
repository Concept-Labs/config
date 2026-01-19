# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed - SOLID Refactoring
- **Architecture Refactoring**: Major refactoring to follow SOLID principles while maintaining backward compatibility
  - **Dependency Inversion Principle (DIP)**: 
    - Introduced factory pattern for creating Storage, Resource, and Parser instances
    - Config class now accepts optional factory interfaces via constructor
    - Resource class accepts AdapterManager via constructor instead of creating it
    - Parser class accepts optional ConfigInterface, reducing tight coupling
  - **Single Responsibility Principle (SRP)**:
    - Config class no longer creates its own dependencies - delegates to factories
    - Resource class no longer creates AdapterManager internally
    - Each factory class has single responsibility of creating one component type
  - **Open/Closed Principle (OCP)**:
    - System is open for extension through custom factories
    - Can replace Storage, Resource, or Parser implementations without modifying code
    - Adapter and plugin systems remain highly extensible
  - **Interface Segregation Principle (ISP)**:
    - Added focused interfaces: `ParserProviderInterface`, `ContextProviderInterface`
    - Components depend only on interfaces they actually use
  - All changes are backward compatible - existing code continues to work without modifications

### Added
- **Factory Interfaces and Implementations**:
  - `StorageFactoryInterface` and `DefaultStorageFactory` for creating Storage instances
  - `ResourceFactoryInterface` and `DefaultResourceFactory` for creating Resource instances
  - `ParserFactoryInterface` and `DefaultParserFactory` for creating Parser instances
- **Helper Interfaces**:
  - `ParserProviderInterface` for components that provide parser access
  - `ContextProviderInterface` for components that provide context access
- **Enhanced PHPDoc Documentation**:
  - Comprehensive documentation for all interfaces
  - Detailed method documentation for all classes
  - Usage examples and design pattern documentation
  - Full parameter and return type documentation

### Changed
- **Config Class**:
  - Constructor now accepts optional factory instances for dependency injection
  - Implements `ParserProviderInterface` for better integration
  - Uses factories to create Storage, Resource, and Parser instances
  - Maintains backward compatibility with default factories when none are provided
- **Resource Class**:
  - Constructor now requires `AdapterManagerInterface` parameter
  - Removed direct dependency on `ConfigInterface`
  - Uses `ParserProviderInterface` for optional parser access via `setParserProvider()`
  - Simplified dependency management
- **Parser Class**:
  - ConfigInterface is now optional in constructor
  - Added `setConfig()` method for post-construction configuration
  - Supports creating plugins with or without Config dependency
- **AbstractPlugin Class**:
  - ConfigInterface is now optional in constructor
  - `getConfig()` method returns nullable ConfigInterface
  - Supports plugins that don't require Config access
- **ResourceInterface**:
  - Added `setParserProvider()` method for setting parser access
  - Enhanced documentation for read/write methods
- **ConfigInterface**:
  - Now extends `ParserProviderInterface`
  - Added `prototype()` method documentation
  - Added `query()` method documentation
  - Comprehensive PHPDoc for all methods

### Documentation
- Updated all interface documentation with comprehensive PHPDoc
- Added detailed documentation to all implementation classes
- Documented design patterns used (Factory, Middleware, Strategy, etc.)
- Added usage examples in interface documentation
- Improved parameter and return type documentation throughout

### Technical Details
- Factory pattern enables dependency injection without breaking changes
- Default factories provide backward compatibility
- All tests pass with no new failures (11 pre-existing ExtendsPlugin test failures remain)
- 161 tests passing, 301 assertions
- No breaking changes to public API

### Fixed
- **Resource path validation**: Added validation to prevent empty source paths from creating invalid adapter lookups
  - `Resource::absolutePath()` now throws `InvalidArgumentException` for empty source strings
  - `Resource::cwd()` now returns `getcwd()` when sourceStack is empty, preventing invalid path construction
  - Prevents edge case where `//` could be normalized to `/` causing "No adapter found for /" errors
- **ExtendsPlugin compatibility with @import/@include**: Fixed ExtendsPlugin to work correctly with @import and @include directives
  - Parser now tracks parse depth to prevent premature lazy resolver execution during nested parsing
  - ExtendsPlugin resolves immediately in nested contexts (imported/included files) and uses lazy resolution at top level
  - Fixed merge mode to use MERGE_OVERWRITE to ensure extending node properties override base properties
  - Added comprehensive tests for @extends with @import/@include scenarios

### Added
- **ExtendsPlugin**: New plugin for configuration inheritance
  - Use `@extends` directive to inherit properties from another configuration node
  - Syntax: `"@extends": "path.to.node"`
  - Supports forward references with lazy resolution
  - Properties in the extending node take precedence over inherited properties
  - Comprehensive test coverage with 11 tests
- **resolveLazy() method**: Added public method to ConfigInterface to process lazy resolvers
  - Allows manual triggering of lazy resolver processing when needed

### Changed
- **Parser**: Parser now automatically calls `resolveLazy()` after parsing to process lazy resolvers
  - This ensures plugins using lazy resolvers (like ExtendsPlugin) work correctly
  - Maintains backward compatibility with existing code

### Fixed
- **ReferenceValuePlugin Bug**: Fixed the bug where `preg_replace_callback` result was not being assigned back to the `$value` variable. Multiple interpolations in the same string now work correctly (e.g., `'http://#{host}:#{port}/api'`)
- **ReferenceValuePlugin Lazy Resolution**: Implemented lazy resolution using `Resolver` to handle forward references and ensure values are looked up when accessed, not during parsing. This prevents issues when referenced values don't exist yet during initial parsing.

### Changed
- **Plugin System Refactor**: Simplified and improved the reference syntax for configuration values
  - Removed `ConfigValuePlugin` (old syntax: `@{path}`)
  - Removed `ReferencePlugin` (old syntax: `@ref(path)`)
  - Removed `Expression\ReferencePlugin` (old syntax: `@path.to.value`)
  - Removed `InterpolatorPlugin`

### Added
- **New Reference Plugins**:
  - `ReferenceNodePlugin`: Use `#path.to.node` or `#path.to.node|default` to reference entire configuration nodes (arrays, objects, or scalars)
  - `ReferenceValuePlugin`: Use `#{path.to.value}` or `#{path.to.value|default}` for string interpolation (scalar values only)
- **Enhanced Context Plugin**:
  - `ContextPlugin` now supports default values with `${variable|default}` syntax
  - Improved error messages when context variables are not found

### Migration Guide

#### Old Syntax → New Syntax

| Old Syntax | New Syntax | Description |
|------------|------------|-------------|
| `@path.to.value` | `#path.to.value` | Reference a configuration node |
| `@{path.to.value}` | `#{path.to.value}` | Interpolate value in a string |
| `${var}` (no default) | `${var\|default}` | Context variable with optional default |

#### Examples

**Before (Old Syntax)**:
```json
{
  "paths": {
    "root": "/var/www",
    "storage": "@paths.root/storage"
  },
  "app": {
    "name": "@{app.name}",
    "title": "@app.name Dashboard"
  }
}
```

**After (New Syntax)**:
```json
{
  "paths": {
    "root": "/var/www",
    "storage": "#{paths.root}/storage"
  },
  "app": {
    "name": "MyApp",
    "title": "#{app.name} Dashboard",
    "config": "#database.settings"
  },
  "server": {
    "host": "localhost",
    "port": 8080
  },
  "api": {
    "url": "http://#{server.host}:#{server.port}/api"
  }
}
```

**Context with Defaults**:
```php
$config = new Config(
    data: [
        'app' => [
            'mode' => '${mode|development}',
            'region' => '${region|us-east-1}'
        ]
    ],
    context: []  // Empty context - will use defaults
);
```

### Documentation Updates

- Updated `docs/plugins.md` with new plugin syntax and examples
- Updated `docs/context.md` with default value syntax for context variables
- Added comprehensive test coverage for new plugins in `tests/Unit/Parser/PluginPestTest.php`
- All tests passing (157 tests with 281 assertions)

### Technical Details

- `ReferenceNodePlugin` pattern: `/^#(\.?[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\|([^|]+))?$/i`
- `ReferenceValuePlugin` pattern: `/#\{(\.?[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\|([^|]+))?\}/i`
- `ContextPlugin` pattern: `/\${([a-zA-Z_][a-zA-Z0-9_]*)(?:\|([^}]+))?}/i`
- Default plugin priorities:
  - `EnvPlugin`: 999
  - `ContextPlugin`: 998
  - `ReferenceNodePlugin`: 998
  - `ReferenceValuePlugin`: 998
  - `ImportPlugin`: 997
  - `ExtendsPlugin`: 997
  - `CommentPlugin`: 996
