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
            // Only one level of reference is resolved here; for nested references, resolve recursively in the callback if needed.
            // Use a Resolver to delay execution until the value is actually needed
            // Reference node may not exist yet during initial parsing
            $value = new Resolver(
                fn() => preg_replace_callback(self::PATTERN, function ($matches)  {
                    $ref = $matches[1];
                    $default = $matches[2] ?? null;

                    if ($this->getConfig()->has($ref)) {
                        $value = $this->getConfig()->get($ref);
                        if (is_array($value) || is_object($value)) {
                            $value = "### Reference '$ref' is not a scalar value ###";
                        }
                    } else {
                        $value = $default ?? "### Reference '$ref' not found ###";
                    }

                    return $value;
                }, $value)
            );
        }

        return $next($value, $path, $subjectData);
    }
}