<?php
namespace Concept\Config\Parser;

use Concept\Config\ConfigInterface;

/**
 * Interface for resolvable values
 * 
 * Resolvable values are lazy-evaluated callables that are resolved during
 * configuration processing. They enable features like forward references,
 * lazy resolution, and deferred evaluation of complex expressions.
 * 
 * When a value in the configuration implements this interface, it will be
 * automatically resolved by calling it with the config instance.
 */
interface ResolvableInterface
{
    /**
     * Resolve the value
     * 
     * This method is called when the resolvable value needs to be evaluated.
     * It receives the full configuration instance, allowing it to access
     * other configuration values, context, or perform complex resolution logic.
     * 
     * The method may return:
     * - A concrete value (string, int, array, etc.)
     * - Another ResolvableInterface (for multi-step resolution)
     * - Any other type needed by the configuration
     * 
     * @param ConfigInterface $config The config instance for accessing configuration data
     * 
     * @return mixed The resolved value
     */
    public function __invoke(ConfigInterface $config): mixed;
}
