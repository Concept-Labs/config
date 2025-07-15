<?php
namespace Concept\Config\Resource;



interface AdapterManagerInterface
{
    /**
     * Register an adapter
     * 
     * @param srting $adapter
     * @param int $priority
     * 
     * @return static
     */
    public function registerAdapter(string $adapter, int $priority = 0): static;
    
    /**
     * Get an adapter for the uri
     * 
     * @param string $uri
     * 
     * @return AdapterInterface
     */
    public function getAdapter(string $uri): AdapterInterface;
    
}