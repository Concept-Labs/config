<?php
namespace Ctl\Config;

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
     * If config has value
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return bool
     */
    function has(string ...$paths): bool;

    /**
     * Get the all config data
     *
     * @return void
     */
    function all();

    /**
     * Set the config data
     *
     * @param array $data The data
     * 
     * @return void
     */
    public function setData(array $data): void;

    /**
     * Saves the current state of the config into stack
     *
     * @return void
     */
    public function pushState(): void;
    
    /**
     * Restore previous state of the config
     *
     * @return void
     */
    public function popState(): void;

    /**
     * Merge into the config data from a values
     *
     * @param array $values The array for merge from
     * 
     * @return void
     */
    public function mergeFrom(array $values):void;


    /**
     * Get self instance with new data
     *
     * @param array $data The data @see setData()
     * 
     * @return ConfigInterface
     */
    public function withData(array $data): ConfigInterface;

    /**
     * Get self instance with data taken by path
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return ConfigInterface
     */
    public function withPath(string ...$paths);

    /**
     * Reset the state of self instance
     *
     * @return void
     */
    public function reset(): void;
}