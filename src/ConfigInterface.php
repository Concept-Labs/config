<?php
namespace Concept\Config;

use Concept\PathAccess\PathAccessInterface;
use IteratorAggregate;

interface ConfigInterface extends PathAccessInterface
{
   
    const AUTLOAD_NODE = 'autoload-config';

    /**
     * Load the config from a file (JSON)
     * @todo: Add support for other formats
     *
     * @param string $path The path to the file
     * 
     * @return self
     */
    public function load(string $path): self;
    
}