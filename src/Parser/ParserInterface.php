<?php
namespace Concept\Config\Parser;

use Concept\Config\Parser\Plugin\PluginInterface;

/**
 * Interface for configuration parsers
 * 
 * Parsers process configuration data through a plugin system, applying
 * transformations, resolving variables, and handling directives. Plugins
 * are executed as middleware in priority order.
 */
interface ParserInterface
{
    /**
     * Special value indicating a node should be abandoned (removed)
     */
    const ABANDONED_NODE = '___ABANDONED___';

    /**
     * Apply plugins to configuration data
     * 
     * Processes the data array through all registered plugins in priority
     * order. Each plugin can transform values, resolve variables, or mark
     * nodes for removal.
     * 
     * @param array &$data The data to process (passed by reference)
     * 
     * @return static The parser instance for method chaining
     */
    public function parse(array &$data): static;

    /**
     * Register a plugin with a priority
     * 
     * Registers a plugin to be executed during parsing. Plugins are executed
     * in priority order (higher numbers first). Multiple plugins can have the
     * same priority and will be executed in registration order within that priority.
     * 
     * The callable should have the signature: 
     * `function (mixed $value, string $path, array &$subjectData, callable $next): mixed`
     * 
     * - The callable must return the modified `$value`
     * - To continue the chain, the `$next` callable should be called
     * - `$next` has the signature: `function (mixed $value, string $path, array &$subjectData): mixed`
     * 
     * @param PluginInterface|callable|string $plugin The plugin to register (object, callable, or class name)
     * @param int $priority The priority (higher = earlier execution, default 0)
     * 
     * @return static The parser instance for method chaining
     */
    public function registerPlugin(PluginInterface|callable|string $plugin, int $priority = 0): static;

    /**
     * Get the current parse depth
     * 
     * Returns the current nesting level of parse() calls. This is useful for
     * plugins that need to behave differently at different depths or prevent
     * infinite recursion.
     * 
     * @return int The current parse depth (0 when not parsing)
     */
    public function getParseDepth(): int;
}
