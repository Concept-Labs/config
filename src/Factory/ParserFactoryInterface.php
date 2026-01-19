<?php

namespace Concept\Config\Factory;

use Concept\Config\Parser\ParserInterface;

/**
 * Factory interface for creating Parser instances
 * 
 * This interface allows for flexible creation of Parser objects,
 * enabling custom plugin configurations and adhering to the
 * Dependency Inversion Principle.
 */
interface ParserFactoryInterface
{
    /**
     * Create a new Parser instance
     * 
     * @return ParserInterface The created parser instance
     */
    public function create(): ParserInterface;
}
