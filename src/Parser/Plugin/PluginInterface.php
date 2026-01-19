<?php
namespace Concept\Config\Parser\Plugin;

/**
 * Interface for parser plugins
 * 
 * Plugins are middleware components that process configuration values during
 * parsing. They can transform values, resolve variables, handle directives,
 * or mark nodes for removal. Plugins are executed in a chain based on priority.
 */
interface PluginInterface
{
    /**
     * Process a configuration value
     * 
     * This method is called for each value in the configuration during parsing.
     * The plugin can:
     * - Transform the value
     * - Resolve variables or references
     * - Return ParserInterface::ABANDONED_NODE to remove the value
     * - Call $next() to continue the plugin chain
     * 
     * Example:
     * ```php
     * public function __invoke($value, $path, &$subjectData, $next) {
     *     if (is_string($value) && str_starts_with($value, '@custom:')) {
     *         $value = $this->processCustomDirective($value);
     *     }
     *     return $next($value, $path, $subjectData);
     * }
     * ```
     * 
     * @param mixed $value The value to process
     * @param string $path The dot-notation path of the value in the configuration
     * @param array &$subjectData Reference to the entire configuration array being parsed
     * @param callable $next The next plugin in the chain
     * 
     * @return mixed The processed value
     */
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed;
}
