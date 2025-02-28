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
     * @param mixed $source The source
     *  Supported sources:
     * - File path (json)
     * - Array
     * - JSON string
     * @todo: Add support for other formats
     * 
     * @return static
     */
    public function load(mixed $source): static;

    /**
     * Export the config to a file
     *
     * @param string $path The file path
     * 
     * @return string The file path
     */
    public function export(string $path): static;


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