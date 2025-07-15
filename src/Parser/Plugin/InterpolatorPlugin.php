<?php

namespace Concept\Config\Parser\Plugin;

use Concept\Arrays\DotArray\DotArray;
use Concept\Arrays\DotArray\DotArrayInterface;

class InterpolatorPlugin extends AbstractPlugin
{
    /**
     * The pattern to match
     * "{{placeholder}}"
     */
    const PATTERN = '/{(.*?)}/';

    private ?DotArrayInterface $context = null;

    /**
     * Set the context.
     * 
     * @param  DotArrayInterface  $context  The context
     * 
     * @return static
     */
    public function setContext(DotArrayInterface $context): static
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get the value from the context.
     * 
     * @param  string  $path  The path to the value
     * 
     * @return mixed
     */
    protected function getContextValue(string $path, string $default): mixed
    {
        return $this->context->get($path, $default);
    }

    /**
     * {@inheritDoc}
     * 
     * Replace placeholders with the value from the context.
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value)) { 
            $value = preg_replace_callback(
                static::PATTERN,
                function ($matches) {
                    return $this->getContextValue($matches[1], $matches[0]);
                },
                $value
            );
        }

        return $next($value, $path, $subjectData);
    }

    public function before(array &$data, callable $next): void
    {
        $next($data);
    }

}