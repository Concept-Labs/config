<?php

namespace Concept\Config\Factory;

use Concept\Config\Storage\StorageInterface;

/**
 * Factory interface for creating Storage instances
 * 
 * This interface allows for flexible creation of Storage objects,
 * enabling dependency injection and testability while maintaining
 * the Single Responsibility and Dependency Inversion principles.
 */
interface StorageFactoryInterface
{
    /**
     * Create a new Storage instance
     * 
     * @param array $data Initial data for the storage
     * 
     * @return StorageInterface The created storage instance
     */
    public function create(array $data = []): StorageInterface;
}
