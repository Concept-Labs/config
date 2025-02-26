<?php

namespace Concept\Config\PathAccess;


interface PathAccessInterface 
{

    /**
     * Initialize the config
     * 
     * @return static
     */
    public static function fromArray(array $data = []): static;

    /**
     * Get the all data
     * 
     * @return array
     */
    public function asArray(): array;

    /**
     * Get the all data by reference
     * 
     * @return array
     */
    public function &asArrayRef(): array;

    /**
     * Set the path separator
     *
     * @param string $separator The separator
     * 
     * @return static
     */
    public function setPathSeparator(string $separator): static;

    /**
     * Check if the data has value by path
     *
     * @param string ...$paths List of paths e.g has("key", "subkey", "subkey")
     *                         Or a path e.g. has("key.subkey.subsubkey")
     *                         First way is preferred because 
     *                         it uses defined separator automatically
     * 
     * @return bool
     */
    function has(string ...$paths): bool;

    /**
     * Get the value by path
     * @see has()
     * 
     * @return mixed
     */
    function get(string ...$paths);

    /**
     * @param string $paths
     * @param mixed $value
     * @see has()
     * 
     * @return static
     */
    function set(string $path, $value): static;

    /**
     * Unset the value by path
     * 
     * @param string ...$paths
     * 
     * @return static
     */
    public function unset(string ...$paths): static;

    /**
     * @deprecated
     * Set the data
     *
     * @param array $data The data
     * 
     * @return static
     */
    public function setData(array $data): static;

    /**
     * Set the data
     *
     * @param array $data The data
     * 
     * @return static
     */
    public function hydrate(array $data): static;


    /**
     * Get self instance with new data
     *
     * @param array $data The data @see setData()
     * 
     * @return static
     */
    public function withData(array $data): static;

    /**
     * Merge into the data from a given data
     *
     * @param array|static $data The array for merge from
     * 
     * @return static
     */
    public function merge(array|PathAccessInterface $data): static;

    /**
     * Merge into the data from a values by path
     *
     * @param string $path The path
     * @param array|PathAccessInterface $data The data for merge from
     * 
     * @return static
     */
    public function mergeTo(string $path, array|PathAccessInterface $data): static;

    /**
     * Move the value from one path to another
     *
     * @param string $from The path from
     * @param string $to The path to
     * 
     * @return static
     */
    public function move(string $from, string $to): static;
    
    /**
     * Get cloned instance with data taken by path
     *
     * @param string ...$paths The paths. @see get()
     * 
     * @return static
     */
    //public function fromPath(string ...$paths);
    public function from(string ...$paths): ?static;

    /**
     * Get the path to the node from which the current config was created
     * Note: The path is relative to the root of the original config
     * 
     * @param string ...$paths
     * 
     * @return array
     */
    public function getCreatedFromPath(): array;


    /**
     * @deprecated
     * Get the path to the  node
     * 
     * @param string ...$paths
     * 
     * @return static
     */
    public function fromPath(string ...$paths): ?static;


    /**
     * Get the path to the  node
     * 
     * @param string ...$paths
     * 
     * @return string
     */
    public function path(string ...$paths): string;

    


    /**
     * Reset the state of self instance
     *
     * @return static
     */
    public function reset(): static;
}