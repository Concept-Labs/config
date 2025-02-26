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
     * @deprecated
     * Load the config from a file (JSON)
     * @todo: Add support for other formats
     *
     * @param string $path The path to the file
     * 
     * @return static
     */
    public function load(string $path): static;

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