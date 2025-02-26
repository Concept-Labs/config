<?php
namespace Concept\Config;

interface ConfigAwareInterface
{
    
    /**
     * Set a config
     *
     * @param ConfigInterface $config The config
     * 
     * @return void
     */
    function setConfig(ConfigInterface $config): static;

    /**
     * Get a config value
     *
     * @param string ...$path The config path. 
     * Example: ("some.path.to.config")
     * Same as: ("some", "path", "to", "config")
     * Same as: ("some", "path.to", "config")
     * Same as: (...["some", "path", "to", "config"])
     * 
     * @return mixed
     */
    public function getConfigValue(string ...$path);

}