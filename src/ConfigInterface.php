<?php
namespace Concept\Config;

interface ConfigInterface
{
    /**
     * The path separatog. e.g. "key.subkey.subsubkey"
     */
    const PATH_SEPARATOR = '.';

    /**
     * Get the config value by path
     *
     * @param string ...$paths An array of paths e.g get("key", "subkey", "subkey")
     *                         Or a path e.g. get("key.subkey.subsubkey")
     *                         First way is prefered because 
     *                         it uses defined separator automatically
     * 
     * @return mixed|array|object
     */
    function get(string ...$paths);

    /**
     * @param string $paths
     * @param mixed $value
     * 
     * @return self
     */
    function set(string $path, $value): self;

    /**
     * If config has value
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return bool
     */
    function has(string ...$paths): bool;

    /**
     * Unset the config value by path
     * 
     * @param string ...$paths
     * 
     * @return self
     */
    public function unset(string ...$paths): self;

    /**
     * @deprecated
     * Get the all config data
     *
     * @return void
     */
    function all();

    /**
     * Get the all config data
     * 
     * @return array
     */
    public function asArray(): array;

    /**
     * Set the config data
     *
     * @param array|null $data The data
     * 
     * @return void
     */
    public function setData(?array $data = null): void;

    /**
     * Saves the current state of the config into stack
     *
     * @return void
     */
    public function pushState(): self;
    
    /**
     * Restore previous state of the config
     *
     * @return void
     */
    public function popState(): self;

    /**
     * @deprecated
     * Merge into the config data from a values
     *
     * @param array $values The array for merge from
     * 
     * @return void
     */
    public function mergeFrom(array $values):void;

    /**
     * Merge into the config data from a values
     *
     * @param array $values The array for merge from
     * 
     * @return void
     */
    public function merge(array $data): self;


    /**
     * Get self instance with new data
     *
     * @param array|null $data The data @see setData()
     * 
     * @return self
     */
    public function withData(?array $data = null): self;

    /**
     * @deprecated
     * Get self instance with data taken by path
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return self
     */
    public function withPath(string ...$paths);
    
    /**
     * Get self instance with data taken by path
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return self
     */
    public function fromPath(string ...$paths);

    /**
     * Create a path from parts
     * 
     * @param string ...$paths
     * 
     * @return string
     */
    public function createPath(string ...$paths): string;

    /**
     * Reset the state of self instance
     *
     * @return void
     */
    public function reset(): void;
}