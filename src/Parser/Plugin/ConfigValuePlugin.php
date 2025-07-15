<?php

namespace Concept\Config\Parser\Plugin;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

class ConfigValuePlugin extends AbstractPlugin
{

    const PATTERN = '/\@{([^}]+)}/i';


    protected function match(string $subject): bool
    {
        return str_contains($subject, '@{');
    }

    /**
     * {@inheritDoc}
     * 
     * Replace @{...} with the value from the another config node (refer).
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            while($this->match($value)) {
                $value = @preg_replace_callback(
                    static::PATTERN,
                    fn ($matches) =>  RecursiveDotApi::get($subjectData, $matches[1])
                        ?? $this->getConfig()->get($matches[1])
                        ?? str_replace('@', '@!', $matches[0]),
                    $value
                );
            }
        }

        return $next($value, $path, $subjectData);
    }
}