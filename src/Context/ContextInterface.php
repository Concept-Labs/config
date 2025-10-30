<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArrayInterface;

/**
 * Interface for configuration context
 * 
 * The context provides runtime variables and values that can be referenced
 * during configuration parsing and value resolution. It typically includes:
 * - Environment variables
 * - Custom runtime values
 * - Application state
 * - User-defined variables
 * 
 * Context values can be accessed by plugins during parsing to resolve
 * variable references and expressions.
 */
interface ContextInterface extends DotArrayInterface
{
    /**
     * Add environment variables to the context
     * 
     * Adds environment variables to a special 'ENV' section in the context.
     * This allows configuration files to reference environment variables
     * using context variable syntax.
     * 
     * Example:
     * ```php
     * $context->withEnv(['DB_HOST' => 'localhost']);
     * // Can be accessed as ${ENV.DB_HOST} in configuration
     * ```
     * 
     * @param array $env Array of environment variables (key => value pairs)
     * 
     * @return static The context instance for method chaining
     */
    public function withEnv(array $env): static;
}
