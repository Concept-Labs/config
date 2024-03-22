<?php
namespace Ctl\Config;

trait ConfigAwareTrait
{
    protected ?ConfigInterface $config = null;

    /**
     * Set a config
     *
     * @param ConfigInterface $config The config
     * 
     * @return void
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * Get a config value
     *
     * @param string ...$path The config path
     * 
     * @return mixed
     */
    public function getConfigValue(string ...$path)
    {
        return $this->config->get(...$path);
    }

    /**
     * Get configuration instance
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

}