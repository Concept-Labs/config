<?php
namespace Cl\Config\DataProvider\File;

trait FileDataProviderCacheKeyTrait
{
    /**
     * Generate the cache key
     *
     * @param string|null $key
     * 
     * @return string
     */
    protected function getCacheKey(?string $key = null): string
    {
        return md5($key ?? $this->getPathname());
    }
}