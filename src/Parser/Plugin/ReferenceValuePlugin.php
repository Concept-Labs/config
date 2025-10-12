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
     * Replace @ref(...) with the corresponding reference.
     * 
     * {@inheritDoc}
     * 
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            while (!$value instanceof Resolver && $this->match($value)) { //nested references
                preg_replace_callback(self::PATTERN, function ($matches) use (&$value) {
                    $ref = $matches[1];
                    $default = $matches[2] ?? null;

                    if ($this->getConfig()->has($ref)) {
                        $value = $this->getConfig()->get($ref);
                        if (is_array($value) || is_object($value)) {
                            $value = "### Reference '$ref' is not a scalar value ###";
                        }
                    } else {
                        $value = new Resolver(
                            fn() => $this->getConfig()->get($ref) ?? $default ?? "### Reference '$ref' not found ###"
                        );
                    }
                }, $value);
            }

        }

        return $next($value, $path, $subjectData);
    }
}