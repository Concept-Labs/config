<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArray;

/**
 * Context implementation for runtime configuration variables
 * 
 * Extends DotArray from concept-labs/arrays to provide dot-notation access
 * to context data. The context holds runtime variables that can be referenced
 * during configuration parsing, such as environment variables, application state,
 * and custom user-defined values.
 * 
 * Context is typically organized into sections:
 * - ENV: Environment variables
 * - Custom sections: User-defined variable groups
 */
class Context extends DotArray implements ContextInterface
{
    /**
     * Add environment variables to the context
     * 
     * Stores environment variables in an 'ENV' section, making them
     * accessible to configuration plugins for variable interpolation.
     * 
     * {@inheritDoc}
     */
    public function withEnv(array $env): static
    {
        return $this->withSection('ENV', $env);
    }

    /**
     * Add a section to the context
     * 
     * Creates or replaces a named section in the context with the provided data.
     * Sections are used to organize related variables and prevent naming conflicts.
     * 
     * @param string $section The section name
     * @param array $data The data to store in the section
     * 
     * @return static The context instance for method chaining
     */
    protected function withSection(string $section, array $data): static
    {
        $this->set($section, $data);

        return $this;
    }
}
