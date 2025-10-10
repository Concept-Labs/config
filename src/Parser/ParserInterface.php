<?php
namespace Concept\Config\Parser;

use Concept\Config\Parser\Plugin\PluginInterface;

interface ParserInterface
{

    const VALUE_TO_REMOVE = '___REMOVE___';

    /**
     * Apply the plugins to the data
     * 
     * @param array &$data  The data to plug
     * 
     * @return static
     */
    public function parse(array &$data): static;


    /**
     * Register a plugin.
     * 
     * @param PluginInterface|callable $plugin The plugin to register.
     * 
     *     The callable should have the signature: 
     *     `function (mixed $value, string $path, callable $next): mixed`
     * 
     *     - The callable must return the modified `$value`.
     *     - To continue the chain, the `$next` callable should be called.
     * 
     *     `$next` should have the signature:  
     *     `function (mixed $value, string $path): mixed`
     * 
     * @param int $priority The priority of the plugin.
     * 
     * @return static
     */
    public function registerPlugin(PluginInterface|callable $plugin, int $priority = 0): static;

    /**
     * Get a plugin by name
     * 
     * @param string $plugin The name of the plugin
     * 
     * @return PluginInterface|callable
     */
    //public function getPlugin(string $plugin): PluginInterface|callable;

}