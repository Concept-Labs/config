# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- **ReferenceValuePlugin Bug**: Fixed the bug where `preg_replace_callback` result was not being assigned back to the `$value` variable. Multiple interpolations in the same string now work correctly (e.g., `'http://#{host}:#{port}/api'`)

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
  - `CommentPlugin`: 996
