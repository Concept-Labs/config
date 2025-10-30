<?php

namespace Concept\Config\Factory;

use Concept\Config\Storage\Storage;
use Concept\Config\Storage\StorageInterface;

/**
 * Default implementation of StorageFactoryInterface
 * 
 * Creates standard Storage instances using the default Storage class.
 * This factory can be replaced with custom implementations for different
 * storage backends or behaviors.
 */
class DefaultStorageFactory implements StorageFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $data = []): StorageInterface
    {
        return new Storage($data);
    }
}
