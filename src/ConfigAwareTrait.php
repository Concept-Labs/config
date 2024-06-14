<?php
namespace Concept\Config;

trait ConfigAwareTrait
{
    protected ?ConfigInterface $config = null;

    /**
     * @todo not deprecated?
     * @deprecated
     * @param ConfigInterface $config
     * 
     * @return self
     */
    // public function withConfig(ConfigInterface $config): self
    // {
    //     $clone = clone $this;
    //     $clone->config = $config;

    //     return $clone;
    // }

    /**
     * @deprecated
     * Set a config
     *
     * @param ConfigInterface $config The config
     * 
     * @return void
     */
    public function setConfig(ConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
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
    public function getConfig(): self
    {
        return $this->config;
    }

}