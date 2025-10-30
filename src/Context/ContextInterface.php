<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArrayInterface;

interface ContextInterface extends DotArrayInterface
{
    /**
     * Add environment variables to the context
     * 
     * @param array $env
     * 
     * @return static
     */
    public function withEnv(array $env): static;
}