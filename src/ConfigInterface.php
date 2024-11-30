<?php
namespace Concept\Config;

use Concept\PathAccess\PathAccessInterface;

interface ConfigInterface extends PathAccessInterface
{
   

    /**
     * @deprecated
     * Load the config from a file (JSON)
     * @todo: Add support for other formats
     *
     * @param string $path The path to the file
     * 
     * @return self
     */
    public function load(string $path): self;

    /**
     * Load the config from a JSON file
     * 
     * @param string $path The path to the file
     * 
     * @return self
     */
    public function loadJsonFile(string $path): self;
    
}