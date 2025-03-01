<?php
namespace Concept\Config;

use Concept\Config\Config;
use Concept\Config\ConfigInterface;

class ConfigManager implements ConfigManagerInterface
{
    /**
     * @var array<int, ConfigInterface>
     */
    private ?ConfigInterface $config = null;

    /**
     * {@inheritDoc}
     */
    public function reset(): static
    {
        $this->config = null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasConfig(): bool
    {
        return null !== $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config ??= new Config();
    }

    /**
     * {@inheritDoc}
     */
    public function addConfig(string|array|ConfigInterface ...$configs): static
    {
        foreach ($configs as $config) {
            $this->addConfigItem($config);
        }

        return $this;
    }

    /**
     * Add a config item
     * 
     * @param string|array|ConfigInterface $config
     * 
     * @return static
     */
    protected function addConfigItem(string|array|ConfigInterface $source): static
    {
        $this->getConfig()->load($source, true);

        return $this;
    }
    
}