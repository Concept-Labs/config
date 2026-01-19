<?php

namespace Concept\Config\Factory;

use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Resource\AdapterManagerInterface;

/**
 * Factory interface for creating Resource instances
 * 
 * This interface allows for flexible creation of Resource objects with
 * custom adapter managers, enabling better separation of concerns and
 * adherence to the Dependency Inversion Principle.
 */
interface ResourceFactoryInterface
{
    /**
     * Create a new Resource instance
     * 
     * @param AdapterManagerInterface|null $adapterManager Optional adapter manager
     * 
     * @return ResourceInterface The created resource instance
     */
    public function create(?AdapterManagerInterface $adapterManager = null): ResourceInterface;
}
