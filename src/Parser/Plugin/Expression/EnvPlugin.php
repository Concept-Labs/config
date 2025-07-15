<?php

namespace Concept\Config\Parser\Plugin\Expression;

use Concept\Config\Parser\Plugin\AbstractPlugin;

class EnvPlugin extends AbstractPlugin
{
    const PATTERN = '/@env\((.*?)\)/i';

    /**
     * Check if the subject contains @env(...)
     * 
     * @param string $subject
     * 
     * @return bool
     */
    protected function match(string $subject): bool
    {
        //Not using regex here because it's slower
        return stripos($subject, '@env(') !== false;
    }

    /**
     * {@inheritDoc}
     * 
     * Replace @env(...) with the corresponding environment variable.
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && $this->match($value)) {

            $value = preg_replace_callback(self::PATTERN, function ($matches) {
                $key = trim($matches[1]);
                return $_ENV[$key]  //$_ENV is faster than getenv() and may be filled with more values
                    ?? getenv($key) //else try getenv()
                    ?? $this->getConfig()->getContext()->get('ENV.'.$key) //or look in the context
                    ?? $matches[0]; //or return the original value
            }, $value);
        }

        return $next($value, $path, $subjectData);
    }
}