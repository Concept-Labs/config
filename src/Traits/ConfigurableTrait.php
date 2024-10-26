<?php
namespace Concept\Config\Traits;

use Concept\Config\ConfigInterface;

trait ConfigurableTrait
{
    private ?ConfigInterface $___config = null;

    /**
     * Set the config
     * 
     * @param ConfigInterface $config
     * 
     * @return self
     */
    public function withConfig(ConfigInterface $config): self
    {
        $this->___config = $config;

        return $this;
    }

    /**
     * Get the config
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        if ($this->___config === null) {
            throw new \RuntimeException('Config not set');
        }

        return $this->___config;
    }
}
    
