<?php
namespace Concept\Config;

use Concept\Config\Adapter\AdapterInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\PathAccess\PathAccessInterface;
use IteratorAggregate;
use JsonSerializable;

interface ConfigInterface 
    extends 
        PathAccessInterface,
        JsonSerializable,
        IteratorAggregate

{

    public function getAdapter(): AdapterInterface;


     /**
     * Get the value by path without processing plugins
     * 
     * @param string $path The path
     * 
     * @return mixed
     */
    public function getRaw(string $path = ''): mixed;

    public function get(string $path = '', bool $forceProcess = false ): mixed;

     
    /**
     * Load the config from a source
     * @todo: Add support for other formats
     *
     * @param string $source The source
     *  Supported sources:
     * - File path (json, php, [yaml: not yet])
     @todo: Add support for other formats
     * 
     * @param bool $preProcess Preprocess the source data
     * 
     * 
     * @return static
     */
    public function load(mixed $source, bool $preProcess = true): static;

    /**
     * Import the config from a source
     *
     * @param string $source The source
     * @param bool $preProcess Preprocess the source data 
     * 
     * @return static
     */
    public function import(mixed $source, bool $preProcess = true): static;

    /**
     * Import the config to a path
     *
     * @param string $path The path
     * @param mixed $source The source
     * @param bool $preProcess Preprocess the source data 
     * 
     * @return static
     */
    public function importTo(string $path, mixed $source, bool $preProcess = true): static;

    /**
     * Export the config to a file
     *
     * @param string $target The file path
     * @param bool $preprocess Preprocess the data before exporting
     * 
     * @return static
     */
    public function export(string $target, bool $preprocess = true): static;

    
    public function withContext(ContextInterface $context): static;

    /**
     * Add to the context
     *
     * @param array $context The context
     * 
     * @return static
     */
    public function addContext(array $context): static;

    /**
     * Get the context
     *
     * @return ContextInterface The context 
     */
    public function getContext(): ContextInterface;

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