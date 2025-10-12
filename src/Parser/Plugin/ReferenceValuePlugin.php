<?php

namespace Concept\Config\Parser\Plugin;

use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

class ReferenceValuePlugin extends AbstractPlugin
{

    // Matches #path, #path.to.node, #path.to, or #path.to|<default>, but not #import(...), #{...}, etc.
    // Allow #. and similar references (dot as first char after #) for relative references
    // Optionally accepts a |<default> fallback after the path
    const PATTERN = '/#\{(\.?[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\|([^|]+))?\}/i';

    

    protected function match(string $subject): bool
    {
        return preg_match(self::PATTERN, $subject);
    }

    /**
     * Replace #{...} with the corresponding reference value.
     * 
     * {@inheritDoc}
     * 
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            // Replace all #{...} patterns with their values
            $value = preg_replace_callback(self::PATTERN, function ($matches) {
                $ref = $matches[1];
                $default = $matches[2] ?? null;

                if ($this->getConfig()->has($ref)) {
                    $refValue = $this->getConfig()->get($ref);
                    
                    // Only scalar values can be interpolated
                    if (is_array($refValue) || is_object($refValue)) {
                        return "### Reference '$ref' is not a scalar value ###";
                    }
                    
                    return $refValue;
                } else {
                    // Use default if provided, otherwise show error
                    return $default ?? "### Reference '$ref' not found ###";
                }
            }, $value);
        }

        return $next($value, $path, $subjectData);
    }
}