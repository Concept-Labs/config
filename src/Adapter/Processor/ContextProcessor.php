<?php
namespace Concept\Config\Adapter\Processor;

use Concept\Config\ConfigInterface;

class ContextProcessor implements ProcessorInterface
{
    public static function getPriority(): int
    {
        return 100;
    }


    public static function process(
        string $key, 
        mixed &$value, 
        ConfigInterface $config, 
        callable $next
    ): void {
        if (is_string($value) && str_contains($value, '${')) {
            
            $value = preg_replace_callback(
                '/\${(.+?)}/',
                function ($matches) use ($config) {
                    return $config->getContext()->get($matches[1]) ?? $matches[0];
                },
                $value,
                -1,
                $count
            );

            // If we replaced any variables, we need to re-process the value
            // It may create circular references
            if ($count > 0) {
                //static::process($key, $value, $config, $next);
            }
        }

        $next($key, $value, $config);
    }
}