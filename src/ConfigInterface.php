<?php

namespace Concept\Config;

use Concept\Arrays\DotArray\DotArrayInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\ResolvableInterface;
use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Contract\ParserProviderInterface;
use IteratorAggregate;

/**
 * Main configuration interface
 * 
 * Defines the contract for configuration management including data access,
 * manipulation, loading, importing, and exporting. Extends ParserProviderInterface
 * to allow components to access the parser when needed.
 */
interface ConfigInterface extends IteratorAggregate, ParserProviderInterface
{

    /**
     * Magic method to get a configuration value by path
     *
     * Provides a convenient way to access configuration values using
     * the config instance as a callable.
     *
     * @param string $path The dot-notation path to the value
     * 
     * @return mixed The configuration value
     */
    public function __invoke(string $path);

    /**
     * Reset the configuration to its initial state
     *
     * Clears all configuration data, context, and internal state,
     * returning the instance to a clean state ready for new data.
     *
     * @return static The config instance for method chaining
     */
    public function reset(): static;

    /**
     * Create a new Config instance from an array
     *
     * Factory method to create a configuration instance with initial
     * data and optional context values.
     *
     * @param array $data The initial configuration data
     * @param array $context Optional context data for variable resolution
     * 
     * @return static A new config instance
     */
    public static function fromArray(array $data, array $context = []): static;

    /**
     * Create a prototype (clean copy) of this configuration
     *
     * Creates a cloned instance and resets it to a clean state,
     * maintaining the same factories and settings but clearing all data.
     *
     * @return static A clean prototype config instance
     */
    public function prototype(): static;

    /**
     * Hydrate the configuration with data
     *
     * Merges the provided data into the existing configuration,
     * replacing values at matching paths.
     *
     * @param array $data The data to merge into the configuration
     * 
     * @return static The config instance for method chaining
     */
    public function hydrate(array $data): static;

    /**
     * Get a reference to the internal configuration data array
     *
     * Returns a reference to the actual data array, allowing direct
     * manipulation. Use with caution as changes bypass validation.
     *
     * @return array Reference to the configuration data
     */
    public function &dataReference(): array;

    /**
     * Convert configuration to array
     *
     * Returns a copy of the entire configuration as a plain PHP array.
     * This is useful for serialization or exporting.
     *
     * @return array The configuration data
     */
    public function toArray(): array;

    /**
     * Get a configuration node as a new Config instance
     *
     * Creates a new configuration instance containing only the data
     * at the specified path. Can optionally create a copy or reference.
     *
     * @param string $path The dot-notation path to the node
     * @param bool $copy Whether to copy the data (true) or reference it (false)
     * 
     * @return static A new config instance for the node
     */
    public function node(string $path, bool $copy = true): static;

    /**
     * Get a configuration value by path
     *
     * Retrieves a value using dot notation. Returns the default value
     * if the path doesn't exist. The returned value is a reference when
     * it exists in the configuration.
     *
     * @param string $key The dot-notation path to the value
     * @param mixed $default The default value if the path doesn't exist
     * 
     * @return mixed The configuration value or default
     */
    public function &get(string $key, mixed $default = null): mixed;

    /**
     * Query the configuration using a query string
     *
     * Executes a query against the configuration data. The query
     * syntax depends on the storage implementation.
     *
     * @param string $query The query string
     * 
     * @return mixed The query result
     */
    public function query(string $query): mixed;

    /**
     * Set a configuration value by path
     *
     * Sets a value at the specified dot-notation path, creating
     * intermediate arrays as needed.
     *
     * @param string $key The dot-notation path
     * @param mixed $value The value to set
     * 
     * @return static The config instance for method chaining
     */
    public function set(string $key, mixed $value): static;

    /**
     * Check if a configuration path exists
     *
     * Returns true if the specified dot-notation path exists in the
     * configuration, false otherwise.
     *
     * @param string $key The dot-notation path to check
     * 
     * @return bool True if the path exists
     */
    public function has(string $key): bool;

    /**
     * Load configuration from a source
     *
     * Replaces the current configuration with data loaded from the
     * specified source. The source can be a file path, array, or
     * another ConfigInterface instance.
     *
     * @param string|array|ConfigInterface $source The source to load from
     * 
     * @return static The config instance for method chaining
     */
    public function load(string|array|ConfigInterface $source): static;

    /**
     * Import configuration from a source
     *
     * Merges configuration data from the specified source into the
     * existing configuration, preserving existing values where there
     * are no conflicts.
     *
     * @param string|array|ConfigInterface $source The source to import from
     * 
     * @return static The config instance for method chaining
     */
    public function import(string|array|ConfigInterface $source): static;

    /**
     * Import configuration to a specific path
     *
     * Imports configuration data and merges it at the specified path
     * in the configuration tree, rather than at the root.
     *
     * @param string|array|ConfigInterface $source The source to import from
     * @param string $path The dot-notation path where to import the data
     * 
     * @return static The config instance for method chaining
     */
    public function importTo(string|array|ConfigInterface $source, string $path): static;

    /**
     * Export configuration to a target file
     *
     * Writes the current configuration to the specified file using
     * the appropriate adapter based on the file extension.
     *
     * @param string $target The target file path
     * 
     * @return static The config instance for method chaining
     */
    public function export(string $target): static;

    /**
     * Replace current context with new values
     *
     * Replaces the entire context with new values, which are used for
     * variable interpolation during parsing and value resolution.
     *
     * @param ContextInterface|array $context The new context data
     * 
     * @return static The config instance for method chaining
     */
    public function withContext(ContextInterface|array $context): static;

    /**
     * Get the context instance
     *
     * Returns the context object which contains runtime variables
     * used for value interpolation and resolution.
     *
     * @return ContextInterface The context instance
     */
    public function getContext(): ContextInterface;

    /**
     * Get the resource instance
     *
     * Returns the resource handler responsible for reading and
     * writing configuration files.
     *
     * @return ResourceInterface The resource instance
     */
    public function getResource(): ResourceInterface;

    /**
     * Add a lazy resolver to the configuration
     * 
     * Lazy resolvers are callables that are executed after all configuration
     * parsing is complete, allowing for forward references and complex
     * resolution logic.
     * 
     * @param ResolvableInterface $resolver The resolver to add
     * 
     * @return static The config instance for method chaining
     */
    public function addLazyResolver(ResolvableInterface $resolver): static;

    /**
     * Process all pending lazy resolvers
     * 
     * Executes all registered lazy resolvers and clears the resolver queue.
     * This is typically called automatically during load/import operations.
     * 
     * @return static The config instance for method chaining
     */
    public function resolveLazy(): static;
   
}
    