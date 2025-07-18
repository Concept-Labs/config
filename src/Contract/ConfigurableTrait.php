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
    public function getConfig(): ?ConfigInterface
    {
        return $this->___config;
    }
}