<?php
namespace Concept\Config;

use Concept\Config\PathAccess\PathAccessInterface;
use IteratorAggregate;
use JsonSerializable;

interface ConfigInterface 
    extends 
        PathAccessInterface,
        JsonSerializable,
        IteratorAggregate

{
    /**
     * @see PathhAccessInterfacevfor more methods
     */
     
    /**
     * Load the config from a source
     * @todo: Add support for other formats
     *
     * @param string $source The source
     *  Supported sources:
     * - File path (json, php, [yaml: not yet])
     @todo: Add support for other formats
     * 
     * @param bool $merge Merge the loaded config with the current config
     * 
     * 
     * @return static
     */
    public function load(string $source, bool $merge = true): static;

    /**
     * Import the config from a source
     *
     * @param string $source The source
     * 
     * @return static
     */
    public function import(string $source): static;

    /**
     * Export the config to a file
     *
     * @param string $target The file path
     * 
     * @return static
     */
    public function export(string $target): static;

    /**
     * Set the context
     *
     * @param array $context The context
     * 
     * @return static
     */
    public function setContext(array $context): static;

    /**
     * Add to the context
     *
     * @param array $context The context
     * 
     * @return static
     */
    public function addContext(array $context): static;

    /**
     * Get the context or a value from the context
     * 
     * @param string|null $path The path
     *
     * @return mixed The context or the value
     */
    public function getContext(?string $path = null): mixed;

    /**
     * Saves the current state  into stack
     *
     * @return void
     */
    public function pushState(): static;
    
    /**
     * Restore previous state 
     *
     * @return void
     */
    public function popState(): static;

    /**
     * Restore the initial state
     *
     * @return void
     */
    public function resetState(): static;
    
}