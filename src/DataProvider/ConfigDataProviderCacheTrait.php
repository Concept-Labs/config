<?php
namespace Cl\Config\DataProvider;


use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

trait ConfigDataProviderCacheTrait
{
    /**
     * The cache item pool
     *
     * @var CacheItemPoolInterface
     */
    private CacheItemPoolInterface|null $_cacheItemPool = null;

    /**
     * Sets the cache item pool
     *
     * @param CacheItemPoolInterface $cacheItemPool 
     * 
     * @return ConfigDataProviderInterface
     */
    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool): ConfigDataProviderInterface
    {
        $this->_cacheItemPool = $cacheItemPool;

        return $this;
    }

    /**
     * Gets the cache item pool
     *
     * @return CacheItemPoolInterface|null
     */
    public function getCacheItemPool(): CacheItemPoolInterface|null
    {
        return $this->_cacheItemPool;
    }

    /**
     * Saves the data to cache
     *
     * @param array  $array 
     * @param string $key 
     * 
     * @return ConfigDataProviderInterface
     */
    public function toCache(array $array, string $key): ConfigDataProviderInterface
    {
        if ($this->getCacheItemPool() instanceof CacheItemPoolInterface) {
            try {
                $this->getCacheItemPool()
                    ->save(
                        $this->getCacheItemPool()
                            ->getItem($key)
                            ->set($array)
                    );
            } catch (CacheException $e) {
                // Assume cache exception
                // Ignore it and keep up to cache implementation
            }
        }
        return $this;
    }

    /**
     * Gets the data from cache
     *
     * @param string $key 
     * 
     * @return array|null
     */
    public function fromCache(string $key): array|null
    {
        $cached = null;
        if ($this->getCacheItemPool() instanceof CacheItemPoolInterface) {
            try {
                $cached = $this->getCacheItemPool()
                    ->getItem($key)
                    ->get();
            } catch (CacheException) {
                // Assume cache exception
                // Ignore it and keep up to cache implementation
            }
        }
        return $cached;
    }
}