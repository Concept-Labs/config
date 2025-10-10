# Documentation Index

Welcome to the Concept\Config documentation! This index will help you find the information you need.

## 📖 Documentation Structure

### Getting Started
- **[Getting Started Guide](./getting-started.md)** - Installation, basic concepts, and your first configuration
  - Installation requirements
  - Basic usage
  - Loading from files
  - Static factory usage
  - Working with context
  - Configuration nodes

### Core Concepts
- **[Architecture](./architecture.md)** - System design and component overview
  - Core components (Config, Storage, Context, Resource, Parser)
  - Plugin system architecture
  - Adapter system
  - Data flow
  - Extension points

- **[Configuration Guide](./configuration.md)** - Complete configuration methods reference
  - Creating configurations
  - Reading and writing values
  - Loading and importing
  - Exporting
  - Working with nodes
  - Best practices

### Advanced Features
- **[Plugin System](./plugins.md)** - Understanding and creating plugins
  - Built-in plugins (EnvPlugin, ReferencePlugin, ImportPlugin, etc.)
  - Creating custom plugins
  - Plugin registration and priorities
  - Common patterns

- **[Adapters](./adapters.md)** - File format support and custom adapters
  - Built-in adapters (JSON, PHP)
  - Creating custom adapters
  - Adapter manager
  - Examples (YAML, INI, XML, HTTP, Database, Redis)

- **[Context & Variables](./context.md)** - Variable resolution and context management
  - Environment variables
  - Custom sections
  - Variable resolution
  - Multi-environment configurations
  - Multi-tenant setups

### Reference
- **[API Reference](./api-reference.md)** - Complete API documentation
  - ConfigInterface
  - StaticFactory
  - Factory
  - ParserInterface
  - PluginInterface
  - ResourceInterface
  - AdapterInterface
  - ContextInterface
  - Exception classes

### Practical Guides
- **[Examples](./examples.md)** - Real-world use cases
  - Basic application configuration
  - Multi-environment setup
  - Database configuration
  - Service container integration
  - Multi-tenant applications
  - Microservices configuration
  - Feature flags
  - Configuration compilation
  - Testing

- **[Advanced Topics](./advanced.md)** - Advanced techniques and patterns
  - Custom factories
  - Advanced plugin development
  - Custom adapters
  - Performance optimization
  - Security considerations
  - Extending the core
  - Integration patterns
  - Error handling

## 🚀 Quick Navigation

### By Task

**I want to...**

- **Get started quickly** → [Getting Started](./getting-started.md)
- **Understand the architecture** → [Architecture](./architecture.md)
- **Load configuration from files** → [Configuration Guide](./configuration.md#loading-configuration)
- **Use environment variables** → [Context & Variables](./context.md#environment-variables)
- **Create a custom plugin** → [Plugin System](./plugins.md#creating-custom-plugins)
- **Add support for YAML/XML** → [Adapters](./adapters.md#creating-custom-adapters)
- **See practical examples** → [Examples](./examples.md)
- **Look up API methods** → [API Reference](./api-reference.md)
- **Optimize performance** → [Advanced Topics](./advanced.md#performance-optimization)
- **Handle multi-tenancy** → [Examples](./examples.md#multi-tenant-application)
- **Implement feature flags** → [Examples](./examples.md#feature-flags)

### By Level

**Beginner**
1. [Getting Started](./getting-started.md)
2. [Configuration Guide](./configuration.md)
3. [Examples](./examples.md)

**Intermediate**
1. [Architecture](./architecture.md)
2. [Plugin System](./plugins.md)
3. [Context & Variables](./context.md)
4. [Adapters](./adapters.md)

**Advanced**
1. [API Reference](./api-reference.md)
2. [Advanced Topics](./advanced.md)

## 📚 Documentation Statistics

- **Total Files**: 10 (including README and index)
- **Total Lines**: ~6,400 lines of documentation
- **Coverage**:
  - ✅ Installation and setup
  - ✅ Basic usage and concepts
  - ✅ Architecture and design
  - ✅ Plugin system (built-in and custom)
  - ✅ Adapter system (built-in and custom)
  - ✅ Context and variable resolution
  - ✅ Complete API reference
  - ✅ Practical examples
  - ✅ Advanced topics
  - ✅ Best practices

## 🔍 Search Tips

Use your editor's search functionality to find specific topics:
- Search for "example" to find code examples
- Search for "usage" to find usage patterns
- Search for class names (e.g., "Config", "Parser") to find API docs
- Search for "@env" or "@import" to find directive documentation

## 🤝 Contributing to Documentation

If you find any issues or want to improve the documentation:
1. Check existing documentation for similar topics
2. Follow the existing style and formatting
3. Include code examples where appropriate
4. Update this index if adding new files


## 🆘 Getting Help

- **Documentation Issues**: Search this documentation first
- **Code Issues**: Check [GitHub Issues](https://github.com/Concept-Labs/config/issues)
- **Questions**: Review [Examples](./examples.md) for common scenarios
- **Feature Requests**: Open a GitHub issue with your use case

## 🔗 External Resources

- **Main Repository**: [Concept-Labs/config](https://github.com/Concept-Labs/config)
- **PHP Documentation**: [PHP Manual](https://www.php.net/manual/)
- **Composer**: [getcomposer.org](https://getcomposer.org/)

---

**Last Updated**: October 2025  
**Documentation Version**: 1.0.0
