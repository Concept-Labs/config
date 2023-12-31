<?php

namespace Cl\Config;

use Cl\Config\Exception\UnableToLoadConfigException;
use Cl\Config\Exception\InvalidPathException;

/**
 * Interface for configuration management.
 */
interface ConfigInterface
{
    /**
     * Get a configuration value by key.
     *
     * @param string $path    The configuration path.
     * @param mixed  $default The default value if the key is not found.
     *
     * @return mixed The configuration value.
     * @throws InvalidPathException If root node not found
     */
    public function get(string $path, mixed $default = null);

    /**
     * Set a configuration value.
     *
     * @param string $path  The configuration path.
     * @param mixed  $value The configuration value.
     *
     * @return ConfigInterface
     * @throws InvalidPathException If node not found
     */
    public function set(string $path, mixed $value): ConfigInterface;

    /**
     * Check if a configuration key exists.
     *
     * @param string $path The configuration path.
     *
     * @return bool True if the key exists, false otherwise.
     * @throws InvalidPathException If root node not found
     */
    public function has(string $path): bool;

    /**
     * Remove a configuration key.
     *
     * @param string $path The configuration key.
     *
     * @return ConfigInterface
     * @throws InvalidPathException If root node not found
     */
    public function remove(string $path): ConfigInterface;

    /**
     * Get all configuration values.
     *
     * @return \Traversable|array All configuration values.
     */
    public function all(): \Traversable|array;

    /**
     * Load Configuration
     *
     * @return boolean
     * 
     * @throws UnableToLoadConfigException
     */
    public function load() : bool;

}