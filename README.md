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

- **Context:** `${context_value}`
- **Expressions:**
    - **Env:** `@ENV(env_value)`
    - **Reference:** `@ref(reference.path)`
    - Custom plugins can be added
- **Import:** `{"@import": "source"}`
- **Include:** `{"node": "@include(source)"}`

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
