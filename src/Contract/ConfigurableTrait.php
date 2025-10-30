<?php
namespace Concept\Config\Contract;

use Concept\Config\ConfigInterface;

trait ConfigurableTrait
{
    protected ?ConfigInterface $___config = null;

    /**
     * Set the config
     *
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config): static
    {
        $this->___config = $config;

        return $this;
    }

    /**
     * Get the config
     *
     * @return ConfigInterface|null
     * @throws \LogicException if config is not set
     */
    public function getConfig(): ConfigInterface
    {
        if ($this->___config === null) {
            throw new \LogicException('Config is not set.');
        }
        
        return $this->___config;
    }

    /**
     * Check if config is set
     *
     * @return bool
     */
    public function hasConfig(): bool
    {
        return $this->___config instanceof ConfigInterface;
    }
}