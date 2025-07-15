<?php

namespace Concept\Config\Parser\Plugin\Expression;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

class ReferencePlugin extends AbstractPlugin
{
    

    

    protected function match(string $subject): bool
    {
        return preg_match('/^@[a-zA-Z0-9_\.\/]+/', $subject);
    }

    /**
     * {@inheritDoc}
     * 
     * Replace @env(...) with the corresponding environment variable.
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            $ref = substr($value, 1);
            
            return //new Resolver(
                //fn () => 
                RecursiveDotApi::get($subjectData, $ref) 
                    ?? $this->getConfig()->get($ref)  
                        ?? $value;
            //);   
        }

        return $next($value, $path, $subjectData);
    }
}