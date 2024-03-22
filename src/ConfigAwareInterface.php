<?php
namespace Ctl\Config;

interface ConfigAwareInterface
{
    
    /**
     * Set a config
     *
     * @param ConfigInterface $config The config
     * 
     * @return void
     */
    function setConfig(ConfigInterface $config): void;

    /**
     * Get a config value
     *
     * @param string ...$path The config path
     * 
     * @return mixed
     */
    public function getConfigValue(string ...$path);

}