<?php
namespace Concept\Config\Storage;

use Concept\Arrays\DotArray\DotQuery\DotQuery;

class Storage 
    extends DotQuery
    implements StorageInterface
{
    /**
     * {@inheritDoc}
     */
    public function query(string $query): mixed
    {
        // Parse and execute query on the storage data
        // For now, use simple dot notation path access
        return $this->get($query);
    }
}