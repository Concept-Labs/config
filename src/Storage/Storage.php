<?php
namespace Concept\Config\Storage;

use Concept\Arrays\DotArray\DotQuery\DotQuery;

/**
 * Configuration storage implementation
 * 
 * Extends DotQuery from concept-labs/arrays to provide dot-notation access
 * to configuration data with additional query capabilities. This class serves
 * as the internal data store for the Config component.
 * 
 * Features:
 * - Dot notation access (e.g., "app.database.host")
 * - Query operations for complex data retrieval
 * - Reference and value access modes
 * - Nested array manipulation
 */
class Storage 
    extends DotQuery
    implements StorageInterface
{
    /**
     * Execute a query on the storage data
     * 
     * Parses and executes a query string against the configuration data.
     * The query syntax depends on the DotQuery implementation but typically
     * supports dot-notation path access with additional operators.
     * 
     * {@inheritDoc}
     */
    public function query(string $query): mixed
    {
        // Parse and execute query on the storage data
        // For now, use simple dot notation path access
        return $this->get($query);
    }
}
