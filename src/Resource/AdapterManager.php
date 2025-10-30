<?php
namespace Concept\Config\Resource;

use Concept\Config\Resource\Exception\InvalidArgumentException;

/**
 * Adapter manager implementation
 * 
 * Manages a registry of file format adapters and selects the appropriate
 * adapter for a given URI. Adapters are registered with priorities to
 * control the order in which they are checked.
 * 
 * The manager uses a lazy instantiation pattern, creating adapter instances
 * only when needed and caching them for reuse.
 */
class AdapterManager implements AdapterManagerInterface
{
    /**
     * Registered adapter classes organized by priority
     * 
     * @var array<int, array<int, string>>
     */
    private array $adapters = [];

    /**
     * Cache of instantiated adapters
     * 
     * @var array<string, AdapterInterface>
     */
    private array $cache = [];

    /**
     * Register an adapter class with a priority
     * 
     * {@inheritDoc}
     */
    public function registerAdapter(string $adapter, int $priority = 0): static
    {
        if (!is_subclass_of($adapter, AdapterInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Adapter %s must implement %s',
                    $adapter,
                    AdapterInterface::class
                )
            );
        }

        $this->adapters[$priority][] = $adapter;

        krsort($this->adapters);

        return $this;
    }

    /**
     * Get an adapter for a URI
     * 
     * Iterates through registered adapters in priority order (highest first)
     * and returns the first adapter that supports the URI.
     * 
     * {@inheritDoc}
     */
    public function getAdapter(string $uri): AdapterInterface
    {
        foreach ($this->adapters as $adapters) {
            foreach ($adapters as $adapter) {
                if ($adapter::supports($uri)) {
                    return $this->getAdapterInstance($adapter);
                }
            }
        }

        throw new InvalidArgumentException("No adapter found for $uri");
    }

    /**
     * Get or create an adapter instance
     *
     * Returns a cached instance if available, otherwise creates and caches
     * a new instance. This ensures only one instance of each adapter class
     * is created.
     *
     * @param string $adapter The adapter class name
     * 
     * @return AdapterInterface The adapter instance
     */
    protected function getAdapterInstance(string $adapter): AdapterInterface
    {
        return $this->cache[$adapter] ??= new $adapter();
    }
}
