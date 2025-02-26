<?php
namespace Concept\Config;

interface ConfigurableInterface
{
    /**
     * Set the config instance. Keep the current instance immutable
     * 
     * @param ConfigInterface $config
     * 
     * @return static
     */
    public function withConfig(ConfigInterface $config): static;

    /**
     * Set the config instance
     * 
     * @param ConfigInterface $config
     * 
     * @return static
     */
    public function setConfig(ConfigInterface $config): static;

    /**
     * Check if the config instance is set
     * 
     * @return bool
     */
    public function hasConfig(): bool;

    /**
     * Get the config instance
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
    
}