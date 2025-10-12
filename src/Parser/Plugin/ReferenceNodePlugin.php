<?php

namespace Concept\Config\Parser\Plugin;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

class ReferenceNodePlugin extends AbstractPlugin
{
    // Matches #path, #path.to.node, #path.to, or #path.to|<default>, but not #import(...), #{...}, etc.
    // Allow #. and similar references (dot as first char after #) for relative references
    // Optionally accepts a |<default> fallback after the path
    const PATTERN = '/^#(\.?[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\|([^|]+))?$/i';

    

    protected function match(string $subject): bool
    {
        return str_starts_with($subject, '#') && preg_match(self::PATTERN, $subject);
    }

    /**
     * Replace references with the corresponding node.
     * 
     * {@inheritDoc}
     * 
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            if (preg_match(self::PATTERN, $value, $matches)) {
            $ref = $matches[1];
            $default = $matches[2] ?? null;
//            $isRelative = str_starts_with($ref, '.');

            if ($this->getConfig()->has($ref)) {
                $value = $this->getConfig()->get($ref);
            } else {
                $value = new Resolver(
                    fn() => $this->getConfig()->get($ref) ?? $default ?? "### Reference '$ref' not found ###"
                );
            }
            }
        }

        return $next($value, $path, $subjectData);
    }
}