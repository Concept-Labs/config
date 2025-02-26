<?php
namespace Concept\Config\Traits;

use Concept\Config\ConfigInterface;
use Concept\Config\Exception\ConfigNotSetException;

trait ConfigurableTrait
{
    private ?ConfigInterface $___config = null;

    /**
     * Set the config
     * 
     * @param ConfigInterface $config
     * 
     * @return static
     */
    public function setConfig(ConfigInterface $config): static
    {
        $this->___config = $config;

        return $this;
    }

    /**
     * Set the config
     * 
     * @param ConfigInterface $config
     * 
     * @return static
     */
    public function withConfig(ConfigInterface $config): static
    {
        return (clone $this)->setConfig($config);
    }

    /**
     * Check if the config is set
     * 
     * @return bool
     */
    public function hasConfig(): bool
    {
        return $this->___config !== null;
    }

    /**
     * Get the config
     * 
     * @return ConfigInterface
     */
    public function getConfig(?string $path = null): ConfigInterface
    {
        if (!$this->hasConfig()) {
            throw new ConfigNotSetException('Config not set');
        }

        return $path ? $this->___config->get($path) : $this->___config;
    }
}
    
