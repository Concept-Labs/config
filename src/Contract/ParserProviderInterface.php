<?php

namespace Concept\Config\Contract;

use Concept\Config\Parser\ParserInterface;

/**
 * Interface for objects that can provide a Parser instance
 * 
 * This interface enables loose coupling between components that need
 * access to a parser, following the Dependency Inversion Principle.
 */
interface ParserProviderInterface
{
    /**
     * Get the parser instance
     * 
     * @return ParserInterface The parser instance
     */
    public function getParser(): ParserInterface;
}
