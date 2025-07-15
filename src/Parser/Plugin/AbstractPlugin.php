<?php
namespace Concept\Config\Parser\Plugin;

use Concept\Config\ConfigInterface;

abstract class AbstractPlugin implements PluginInterface
{

    /**
     * Constructor
     * 
     * @param ConfigInterface $config
     */
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * Get the config
     * 
     * @return ConfigInterface
     */
    protected function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function before(array &$data, callable $next): void
    {
        $next($data);
    }

    /**
     * {@inheritDoc}
     */
    public function after(array &$data, callable $next): void
    {
        $next($data);
    }
}