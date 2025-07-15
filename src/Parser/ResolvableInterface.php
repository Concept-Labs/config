<?php
namespace Concept\Config\Parser;

interface ResolvableInterface
{
    /**
     * Resolve the value
     * 
     * @return mixed
     */
    public function __invoke(): mixed;
}