<?php

namespace Concept\Config\Parser\Plugin;


use Concept\Config\Parser\Plugin\AbstractPlugin;

class ContextPlugin extends AbstractPlugin
{
    // Matches ${variable} or ${variable|default}
    const PATTERN = '/\${([a-zA-Z_][a-zA-Z0-9_]*)(?:\|([^}]+))?}/i';

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
            while ($this->match($value)) { //replace all matches
                $value = preg_replace_callback(
                    static::PATTERN,
                    function ($matches) {
                        $contextValue = $this->getContextValue($matches[1]);
                        if ($contextValue !== null) {
                            return $contextValue;
                        }
                        // Use default if provided, else keep original
                        return isset($matches[2]) ? $matches[2] : "### Reference '{$matches[1]}' not found ###";
                    },
                    $value
                );
            }
        }

        return $next($value, $path, $subjectData);
    }
}