<?php

namespace Concept\Config\Plugin;

use Concept\Config\ConfigInterface;

class PluginManager implements PluginManagerInterface
{
    private ?ConfigInterface $config = null;

    /** @var array<int, ConfigPluginInterface[]> */
    private array $plugins = [];

    /** @var callable|null */
    private  $stack = null;

    /**
     * PluginManager constructor.
     * @param ConfigInterface $config
     */
    public function __construct()
    {
    }

    // public function setConfigInstance(ConfigInterface $config): static
    // {
    //     $this->config = $config;
    //    // $this->init();
    //     return $this;
    // }

    /**
     * @return ConfigInterface
     */
    // private function getConfig(): ConfigInterface
    // {
    //     return $this->config;
    // }

    private function init()
    {
        $this->plugins = [];
        $this->stack = null;
        foreach ($this->getConfig()->get('plugins', []) as $plugin) {
            $this->add($plugin);
        }
    }

    /**
     * Add a plugin to the manager
     * 
     * @param ConfigPluginInterface $plugin
     * @param int|null $priority
     * @return $this
     */
    public function add(ConfigPluginInterface $plugin, ?int $priority = null): static
    {
        $priority = $priority ?? $plugin->getPriority() ?? 0;
        $this->plugins[$priority][] = $plugin;
        krsort($this->plugins);
        $this->stack = null;
        return $this;
    }

    /**
     * Process a value through the plugin stack
     * 
     * @param string $path
     * @param mixed $value
     * @return mixed
     */
    public function process(string $path, mixed $value, ConfigInterface $config): mixed
    {
        return ($this->getMiddlewareStack())($path, $value);
    }

    /**
     * @return callable
     */
    private function getMiddlewareStack(): callable
    {
        if ($this->stack === null) {
            $this->stack = $this->buildMiddlewareStack();
        }

        return $this->stack;
    }

    /**
     * Build the middleware stack
     * 
     * @return callable
     */
    private function buildMiddlewareStack(): callable
    {
        $next = function ($path, $value, $config) {
            return $value;
        };

        foreach ($this->getPlugins() as $plugin) {
            $next = function ($path, $value, $config) use ($plugin, $next) {
                return $plugin->process($path, $value, $config, $next);
            };
        }

        return $next;
    }

    /**
     * Get the plugins
     * 
     * @return iterable
     */
    private function getPlugins(): iterable
    {
        foreach ($this->plugins as $pluginsAtPriority) {
            foreach ($pluginsAtPriority as $plugin) {
                yield $plugin;
            }
        }
    }
}