<?php
namespace Concept\Config\Resource;

/**
 * Interface for adapter managers
 * 
 * The adapter manager is responsible for registering adapters and selecting
 * the appropriate adapter for a given URI. It maintains a registry of adapters
 * with priorities and uses the adapter's supports() method to determine which
 * adapter should handle a specific file or resource.
 */
interface AdapterManagerInterface
{
    /**
     * Register an adapter with a priority
     * 
     * Registers an adapter class to handle specific file formats. Adapters
     * with higher priority are checked first when selecting an adapter for a URI.
     * Multiple adapters can have the same priority.
     * 
     * @param string $adapter The fully qualified adapter class name
     * @param int $priority The priority (higher = checked first, default 0)
     * 
     * @return static The manager instance for method chaining
     * @throws \Concept\Config\Resource\Exception\InvalidArgumentException If the adapter class is invalid
     */
    public function registerAdapter(string $adapter, int $priority = 0): static;
    
    /**
     * Get an adapter for a URI
     * 
     * Finds and returns an adapter that can handle the given URI by checking
     * registered adapters in priority order. The first adapter whose supports()
     * method returns true is selected and returned.
     * 
     * @param string $uri The URI to find an adapter for (file path, URL, etc.)
     * 
     * @return AdapterInterface An adapter instance capable of handling the URI
     * @throws \Concept\Config\Resource\Exception\InvalidArgumentException If no adapter supports the URI
     */
    public function getAdapter(string $uri): AdapterInterface;
}
