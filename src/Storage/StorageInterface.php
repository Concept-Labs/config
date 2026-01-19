<?php
namespace Concept\Config\Storage;

use Concept\Arrays\DotArray\DotArrayInterface;
use Concept\Arrays\DotArray\DotQuery\DotQueryInterface;

/**
 * Interface for configuration storage
 * 
 * The storage component is responsible for holding and managing configuration
 * data with dot-notation access patterns. It extends DotQueryInterface to provide
 * advanced querying capabilities beyond simple path-based access.
 * 
 * Storage implementations should handle:
 * - Nested data access via dot notation (e.g., "database.host")
 * - Data manipulation (set, get, has, unset)
 * - Query operations for complex data retrieval
 * - Reference and copy semantics for data access
 */
interface StorageInterface extends DotQueryInterface
{
}
