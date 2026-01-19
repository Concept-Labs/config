<?php
namespace Concept\Config\Parser\Plugin;

use Concept\Config\ConfigInterface;

/**
 * Abstract base class for parser plugins
 * 
 * Provides common functionality for plugins including optional access to
 * the config instance. Plugins that don't need config can work without it,
 * maintaining loose coupling.
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * Optional config instance
     * 
     * @var ConfigInterface|null
     */
    private ?ConfigInterface $config = null;

    /**
     * Constructor
     * 
     * @param ConfigInterface|null $config Optional config instance
     */
    public function __construct(?ConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * Get the config instance
     * 
     * @return ConfigInterface|null The config instance or null if not available
     */
    protected function getConfig(): ?ConfigInterface
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