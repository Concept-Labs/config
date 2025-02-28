# Concept Labs Config

Welcome to `Concept-Labs/config` — a flexible and efficient configuration management library for PHP. This package is designed as part of the `Singularity` framework but can be used as a standalone component.

## Key Features
- **Path-Based Access**: Access configuration data using dot-separated paths (e.g., `path.to.key`).
- **Plugin System**: Extensible plugin architecture for processing configuration values (e.g., `${var}` substitution).
- **Compilation**: Optimize performance by compiling configurations into JSON for fast loading.
- **Caching**: Integration with PSR-16 (`SimpleCache`) for caching processed values.
- **Context Support**: Dynamic variable substitution using a context (e.g., `${HOME}`).

## Installation
Install the package via Composer:

```bash
composer require concept-labs/config
