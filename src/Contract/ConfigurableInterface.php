<?php
namespace Concept\Config\Contract;

use Concept\Config\ConfigInterface;

interface ConfigurableInterface
{
    /**
     * Create a new instance of the class with the given config.
     *
     * @param ConfigInterface $config
     *
     * @return static
     */
    //public function withConfig(ConfigInterface $config): static;

    /**
     * Set the config.
     *
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config): static;

    /**
     * Get the config.
     *
     * @return ConfigInterface|null
     * @throws \LogicException if config is not set
     */
    public function getConfig(): ?ConfigInterface;


    /**
     * Check if config is set.
     *
     * @return bool
     */
    public function hasConfig(): bool;

    /**
     * Configure the instance with the given config.
     *
     * @param ConfigInterface $config
     *
     * @return static
     */
    //public function configure(ConfigInterface $config): static;

}