<?php

namespace Concept\Config;

use Concept\Arrays\DotArray\DotArrayInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\ResolvableInterface;
use Concept\Config\Resource\ResourceInterface;
use IteratorAggregate;

interface ConfigInterface extends IteratorAggregate //, DotArrayInterface
{

    /**
     * Reset the configuration to its initial state
     *
     * @return static
     */
    public function reset(): static;

    /**
     * Create a new Config instance from an array
     *
     * @param array $data
     * @param array $context
     * @return static
     */
    public static function fromArray(array $data, array $context = []): static;

    /**
     * Hydrate the configuration with data
     *
     * @return void
     */
    public function hydrate(array $data): static;

    /**
     * Get the configuration data as a reference
     *
     * @return array
     */
    public function &dataReference(): array;

    /**
     * Convert config to array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Convert config to dot array
     *
     * @return DotArrayInterface
     */
    //public function dotArray(): DotArrayInterface;

    /**
     * Get a node by key
     *
     * @param string $key
     * @param bool $copy
     * 
     * @return static
     */
    public function node(string $path, bool $copy = true): static;

    /**
     * Get a value by key
     *
     * @param string $key
     * @param mixed $default
     * 
     * @return mixed
     */
    public function &get(string $key, mixed $default = null): mixed;

    /**
     * Set a value by key
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, mixed $value): static;

    /**
     * Check if a key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove a value by key
     *
     * @param string $key
     * @return static
     */
    //public function remove(string $key): static;

    /**
     * Load configuration from a source
     *
     * @param string|array|ConfigInterface $source
     * 
     * @return static
     */
    public function load(string|array|ConfigInterface $source): static;

    /**
     * Import configuration from a source
     *
     * @param string|array|ConfigInterface $source
     * 
     * @return static
     */
    public function import(string|array|ConfigInterface $source): static;

    /**
     * Import configuration to a specific path
     *
     * @param string|array|ConfigInterface $source
     * @param string $path
     * 
     * @return static
     */
    public function importTo(string|array|ConfigInterface $source, string $path): static;

    /**
     * Export configuration to a target file
     *
     * @param string $target
     * @return static
     */
    public function export(string $target): static;

    /**
     * Replace Current context with new values
     *
     * @return static
     */
    public function withContext(ContextInterface|array $context): static;


    /**
     * Get the context
     *
     * @return ContextInterface
     */
    public function getContext(): ContextInterface;

    /**
     * Get the resource instance
     *
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface;

    /**
     * Get the storage instance
     *
     * @return DotArrayInterface
     */
    public function getParser(): ParserInterface;

    /**
     * Add a resolver to the config
     * 
     * @param ResolvableInterface $resolver The resolver to add
     * 
     * @return static
     */
    public function addLazyResolver(ResolvableInterface $resolver): static;
   
}
    