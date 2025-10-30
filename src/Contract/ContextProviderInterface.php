<?php

namespace Concept\Config\Contract;

use Concept\Config\Context\ContextInterface;

/**
 * Interface for objects that can provide a Context instance
 * 
 * This interface enables loose coupling between components that need
 * access to context data, following the Dependency Inversion Principle.
 */
interface ContextProviderInterface
{
    /**
     * Get the context instance
     * 
     * @return ContextInterface The context instance
     */
    public function getContext(): ContextInterface;
}
