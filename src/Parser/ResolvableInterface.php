<?php
namespace Concept\Config\Parser;

use Concept\Config\ConfigInterface;

interface ResolvableInterface
{
    /**
     * Resolve the value
     * 
     * @param ConfigInterface $config The current config
     * 
     * @return mixed
     */
    public function __invoke(ConfigInterface $config): mixed;
}