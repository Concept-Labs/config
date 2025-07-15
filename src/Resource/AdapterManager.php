<?php
namespace Concept\Config\Resource;

use Concept\Config\Resource\Exception\InvalidArgumentException;

class AdapterManager implements AdapterManagerInterface
{
    private array $adapters = [];
    private array $cache = [];

    /**
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
     * Get an adapter instance.
     *
     * @param string $adapter
     * 
     * @return AdapterInterface
     */
    protected function getAdapterInstance(string $adapter): AdapterInterface
    {
        return $this->cache[$adapter] ??= new $adapter();
    }
}