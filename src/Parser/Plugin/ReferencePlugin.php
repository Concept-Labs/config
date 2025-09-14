<?php

namespace Concept\Config\Parser\Plugin;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

class ReferencePlugin extends AbstractPlugin
{
    const PATTERN = '/@\((.*?)\)/i';

    

    protected function match(string $subject): bool
    {
        return str_starts_with($subject, '@ref(');
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
            $ref = preg_replace(self::PATTERN, '$1', $value);
            //$value = $this->getConfig()->get($ref) ?? $value;
            
            $value = new Resolver(
                fn () => RecursiveDotApi::get($subjectData, $ref) 
                    ?? $this->getConfig()->get($ref)  
                        ?? $value
            );
            
        }

        return $next($value, $path, $subjectData);
    }
}