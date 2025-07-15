<?php

namespace Concept\Config\Parser\Plugin;


use Concept\Config\Parser\Plugin\AbstractPlugin;

class ContextPlugin extends AbstractPlugin
{

    const PATTERN = '/\${([^}]+)}/i';

    /**
     * Get the value from the context
     * 
     * @param string $path
     * 
     * @return mixed
     */
    protected function getContextValue(string $path): mixed
    {
        return $this->getConfig()
            ->getContext()->get($path);
    }

    /**
     * Check if the subject contains ${...}
     * 
     * @param string $subject
     * 
     * @return bool
     */
    protected function match(string $subject): bool
    {
        return str_contains($subject, '${'); //faster than regex
    }

    /**
     * {@inheritDoc}
     * 
     * Replace ${...} with the value from the context.
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {
            while ($this->match($value)) {   
                $value = preg_replace_callback(
                    static::PATTERN,
                    fn ($matches) => $this->getContextValue($matches[1]) 
                        ?? str_replace('$', '$!', $matches[0]),
                    $value
                );
            }
        }

        return $next($value, $path, $subjectData);
    }
}