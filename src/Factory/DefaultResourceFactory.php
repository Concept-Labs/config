<?php

namespace Concept\Config\Factory;

use Concept\Config\Resource\Resource;
use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Resource\AdapterManager;
use Concept\Config\Resource\AdapterManagerInterface;
use Concept\Config\Resource\Adapter\JsonAdapter;
use Concept\Config\Resource\Adapter\PhpAdapter;

/**
 * Default implementation of ResourceFactoryInterface
 * 
 * Creates standard Resource instances with a pre-configured adapter manager
 * that supports JSON and PHP file formats. Custom factories can provide
 * different adapter configurations.
 */
class DefaultResourceFactory implements ResourceFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(?AdapterManagerInterface $adapterManager = null): ResourceInterface
    {
        $adapterManager ??= $this->createDefaultAdapterManager();
        
        return new Resource($adapterManager);
    }
    
    /**
     * Create a default adapter manager with standard adapters
     * 
     * @return AdapterManagerInterface
     */
    protected function createDefaultAdapterManager(): AdapterManagerInterface
    {
        return (new AdapterManager())
            ->registerAdapter(JsonAdapter::class)
            ->registerAdapter(PhpAdapter::class);
    }
}
