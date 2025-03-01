<?php
namespace Concept\Config\Plugin;

use Concept\Config\ConfigInterface;

interface PluginManagerInterface
{
    /**
     * Add a plugin to the manager
     * 
     * @param ConfigPluginInterface $plugin
     * @param int|null $priority
     * 
     * @return static
     */
    public function add(ConfigPluginInterface $plugin, ?int $priority = null): static;

    /**
     * Process a value through the plugin stack
     * 
     * @param string $path
     * @param mixed $value
     * 
     * @return mixed
     */
    public function process(string $path, mixed $value, ConfigInterface $config): mixed;

    //public function setConfigInstance(ConfigInterface $config): static;

}