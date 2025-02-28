<?php
namespace Concept\Config;

use Concept\Config\ConfigInterface;

interface ConfigManagerInterface
{
    /**
     * Reset the config
     * 
     * @return static
     */
    public function reset(): static;

    /**
     * Check if a config is set
     * 
     * @return bool
     */
    public function hasConfig(): bool;

    /**
     * Add the config
     * 
     * @return static
     */
    public function addConfig(string|array|ConfigInterface ...$configs): static;

    /**
     * Get the config
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;


    
    //public function addDecorator(string $decorator): static;
}