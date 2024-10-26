<?php
namespace Concept\Config;

interface ConfigurableInterface
{
    /**
     * Set the config instance
     * 
     * @param ConfigInterface $config
     * 
     * @return static
     */
    public function withConfig(ConfigInterface $config): self;

    /**
     * Get the config instance
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
    
}